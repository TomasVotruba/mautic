<?php

declare(strict_types=1);

namespace Utils\Rector;

use PhpParser\Modifiers;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Param;
use PhpParser\Node\PropertyItem;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Property;
use PhpParser\NodeFinder;
use PHPStan\Type\ObjectType;
use Rector\NodeManipulator\ClassDependencyManipulator;
use Rector\PhpParser\AstResolver;
use Rector\PostRector\ValueObject\PropertyMetadata;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Replaces the argument-less $model->getRepository() service locator with the concrete repository service.
 *
 * Mautic models expose getRepository() returning their custom repository, e.g.
 * FormModel::getRepository(): FormRepository { return $this->formRepository; }. Going through the model
 * just to reach its repository hides the repository dependency and couples the caller to the whole model.
 *
 * Three shapes, picked from the surrounding class and the call receiver:
 *
 *   1. Test cases (Symfony KernelTestCase descendants) get the container lookup - a test cannot take a
 *      service through its PHPUnit-owned constructor:
 *        $tagModel->getRepository()  ->  self::getContainer()->get(TagRepository::class)
 *
 *   2. The model reaching its own repository already holds the property - just swap the call for it:
 *        $this->getRepository()->getNotificationList(...)  ->  $this->notificationRepository->getNotificationList(...)
 *
 *   3. Another object reaching a model's repository gets the repository injected and drops the model hop:
 *        $this->formModel->getRepository()->findOneById(...)  ->  $this->formRepository->findOneById(...)
 *      with "private readonly FormRepository $formRepository" added to the constructor.
 *
 * Only argument-less getRepository() calls on an AbstractCommonModel are matched; the Doctrine entity
 * manager's getRepository(SomeEntity::class) is handled by GetRepositoryToRepositoryServiceRector instead.
 */
final class ModelGetRepositoryToRepositoryServiceRector extends AbstractRector
{
    /**
     * Any class extending this is treated as a test and gets the container lookup.
     */
    private const KERNEL_TEST_CASE = 'Symfony\Bundle\FrameworkBundle\Test\KernelTestCase';

    private const ABSTRACT_COMMON_MODEL = 'Mautic\CoreBundle\Model\AbstractCommonModel';

    /**
     * Generic repository bases - a model that does not override getRepository() resolves to one of these,
     * and there is no dedicated service to depend on.
     */
    private const GENERIC_REPOSITORIES = [
        'Doctrine\ORM\EntityRepository',
        'Mautic\CoreBundle\Entity\CommonRepository',
    ];

    public function __construct(
        private readonly AstResolver $astResolver,
        private readonly ClassDependencyManipulator $classDependencyManipulator,
        private readonly NodeFinder $nodeFinder,
    ) {
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Replace argument-less $model->getRepository() with the concrete repository service',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
$form = $this->formModel->getRepository()->findOneById($id);
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
$form = $this->formRepository->findOneById($id);
CODE_SAMPLE
                ),
            ]
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (!$node instanceof Class_) {
            return null;
        }

        if ($node->isAbstract() || $node->isAnonymous()) {
            return null;
        }

        $getRepositoryCalls = $this->findModelGetRepositoryCalls($node);
        if ([] === $getRepositoryCalls) {
            return null;
        }

        $isTestCase = $this->isObjectType($node, new ObjectType(self::KERNEL_TEST_CASE));
        $hasChanged = false;

        foreach ($getRepositoryCalls as $getRepositoryCall) {
            $repositoryClass = $this->resolveRepositoryClass($getRepositoryCall);
            if (null === $repositoryClass) {
                continue;
            }

            $replacement = $this->resolveReplacement($node, $getRepositoryCall, $repositoryClass, $isTestCase);

            $this->replaceNode($node, $getRepositoryCall, $replacement);
            $hasChanged = true;
        }

        return $hasChanged ? $node : null;
    }

    /**
     * Collects every argument-less $model->getRepository() call on an AbstractCommonModel inside the class.
     *
     * @return MethodCall[]
     */
    private function findModelGetRepositoryCalls(Class_ $class): array
    {
        /** @var MethodCall[] $methodCalls */
        $methodCalls = $this->nodeFinder->findInstanceOf($class, MethodCall::class);

        return array_values(array_filter($methodCalls, function (MethodCall $methodCall): bool {
            if (!$this->isName($methodCall->name, 'getRepository')) {
                return false;
            }

            // The entity manager form takes an argument and is handled by the sibling rule.
            if ([] !== $methodCall->args) {
                return false;
            }

            return $this->isObjectType($methodCall->var, new ObjectType(self::ABSTRACT_COMMON_MODEL));
        }));
    }

    /**
     * The static type of $model->getRepository() is the concrete repository - unless the model leaves it
     * as a generic base, in which case there is no dedicated service to depend on.
     */
    private function resolveRepositoryClass(MethodCall $methodCall): ?string
    {
        $classNames = $this->getType($methodCall)->getObjectClassNames();
        if (1 !== count($classNames)) {
            return null;
        }

        $repositoryClass = $classNames[0];
        if (in_array($repositoryClass, self::GENERIC_REPOSITORIES, true)) {
            return null;
        }

        return $repositoryClass;
    }

    /**
     * Picks the replacement node for the matched call and performs any needed injection as a side effect.
     */
    private function resolveReplacement(Class_ $class, MethodCall $methodCall, string $repositoryClass, bool $isTestCase): Node
    {
        if ($isTestCase) {
            return $this->createContainerGet($repositoryClass);
        }

        $propertyName = $this->resolvePropertyName($repositoryClass);

        // The model reaching its own repository already holds the property - no injection needed.
        if (!$this->isCallOnThis($methodCall)) {
            $this->injectRepository($class, $repositoryClass, $propertyName);
        }

        return new PropertyFetch(new Variable('this'), $propertyName);
    }

    private function isCallOnThis(MethodCall $methodCall): bool
    {
        return $methodCall->var instanceof Variable && $this->isName($methodCall->var, 'this');
    }

    /**
     * Adds the repository dependency, via constructor promotion or - when the class only inherits a
     * constructor it would otherwise have to override - via a #[Required] autowire method.
     */
    private function injectRepository(Class_ $class, string $repositoryClass, string $propertyName): void
    {
        if ($this->shouldUseAutowireMethod($class)) {
            $this->addAutowireDependency($class, $repositoryClass, $propertyName);

            return;
        }

        $this->classDependencyManipulator->addConstructorDependency(
            $class,
            new PropertyMetadata($propertyName, new ObjectType($repositoryClass))
        );
    }

    /**
     * A class without its own constructor that inherits one cannot gain a promoted property without
     * declaring a full constructor override that forwards every parent argument. Mautic injects into
     * such classes with an autowire method instead.
     */
    private function shouldUseAutowireMethod(Class_ $class): bool
    {
        // Its own constructor can take one more promoted parameter safely.
        if ($class->getMethod('__construct') instanceof ClassMethod) {
            return false;
        }

        if (!$class->extends instanceof Name) {
            return false;
        }

        $parentClass = $this->getName($class->extends);
        if ('' === (string) $parentClass) {
            return false;
        }

        // Only an inherited constructor would have to be overridden.
        return $this->astResolver->resolveClassMethod($parentClass, '__construct') instanceof ClassMethod;
    }

    /**
     * Injects through "autowire<ShortClassName>()", reusing that method when the class already has one.
     */
    private function addAutowireDependency(Class_ $class, string $repositoryClass, string $propertyName): void
    {
        // Already injected by an earlier call for the same repository.
        if ($class->getProperty($propertyName) instanceof Property) {
            return;
        }

        $param      = new Param(
            new Variable($propertyName),
            null,
            new FullyQualified($repositoryClass)
        );
        $assignment = new Expression(
            new Assign(new PropertyFetch(new Variable('this'), $propertyName), new Variable($propertyName))
        );

        $autowireClassMethod = $this->resolveAutowireClassMethod($class);

        if ($autowireClassMethod instanceof ClassMethod) {
            $autowireClassMethod->params[] = $param;
            $autowireClassMethod->stmts[]  = $assignment;
        } else {
            $autowireClassMethod = $this->createAutowireClassMethod($class, $param, $assignment);
            array_unshift($class->stmts, $autowireClassMethod);
        }

        array_unshift($class->stmts, $this->createProperty($repositoryClass, $propertyName));
    }

    private function resolveAutowireClassMethod(Class_ $class): ?ClassMethod
    {
        $autowireMethodName = $this->resolveAutowireMethodName($class);

        foreach ($class->getMethods() as $classMethod) {
            if ($this->isName($classMethod->name, $autowireMethodName)) {
                return $classMethod;
            }
        }

        return null;
    }

    private function createAutowireClassMethod(Class_ $class, Param $param, Expression $assignment): ClassMethod
    {
        $classMethod             = new ClassMethod($this->resolveAutowireMethodName($class));
        $classMethod->flags      = Modifiers::PUBLIC;
        $classMethod->params     = [$param];
        $classMethod->returnType = new Identifier('void');
        $classMethod->stmts      = [$assignment];

        // Symfony calls every #[Required] method on service instantiation.
        $classMethod->attrGroups = [
            new AttributeGroup([new Attribute(new FullyQualified('Symfony\Contracts\Service\Attribute\Required'))]),
        ];

        return $classMethod;
    }

    private function createProperty(string $repositoryClass, string $propertyName): Property
    {
        $property       = new Property(Modifiers::PRIVATE, [new PropertyItem($propertyName)]);
        $property->type = new FullyQualified($repositoryClass);

        return $property;
    }

    /**
     * The name is fixed by AutowireMethodNameMustMatchClassRule: "autowire" + the short class name.
     */
    private function resolveAutowireMethodName(Class_ $class): string
    {
        return 'autowire'.$class->name?->toString();
    }

    /**
     * Builds self::getContainer()->get(SomeRepository::class).
     */
    private function createContainerGet(string $repositoryClass): MethodCall
    {
        return new MethodCall(
            new StaticCall(new Name('self'), 'getContainer'),
            'get',
            [new Arg(new ClassConstFetch(new FullyQualified($repositoryClass), 'class'))]
        );
    }

    /**
     * Mautic\FormBundle\Entity\FormRepository -> formRepository.
     */
    private function resolvePropertyName(string $repositoryClass): string
    {
        $shortName = (string) strrchr('\\'.$repositoryClass, '\\');

        return lcfirst(ltrim($shortName, '\\'));
    }

    /**
     * Swaps the matched call for its replacement, in place, wherever it sits in the class.
     */
    private function replaceNode(Class_ $class, MethodCall $oldNode, Node $newNode): void
    {
        $this->traverseNodesWithCallable($class, static function (Node $node) use ($oldNode, $newNode): ?Node {
            return $node === $oldNode ? $newNode : null;
        });
    }
}
