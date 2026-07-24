<?php

declare(strict_types=1);

namespace Utils\Rector;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeFinder;
use PHPStan\Type\ObjectType;
use Rector\NodeManipulator\ClassDependencyManipulator;
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
 * Two shapes, picked from the call receiver:
 *
 *   1. The model reaching its own repository - replace with the already-injected property:
 *        $this->getRepository()->getNotificationList(...)  ->  $this->notificationRepository->getNotificationList(...)
 *
 *   2. Another object reaching a model's repository - inject the repository and drop the model hop:
 *        $this->formModel->getRepository()->findOneById(...)  ->  $this->formRepository->findOneById(...)
 *      with "private readonly FormRepository $formRepository" added to the constructor.
 *
 * Only argument-less getRepository() calls on an AbstractCommonModel are matched; the Doctrine entity
 * manager's getRepository(SomeEntity::class) is handled by GetRepositoryToRepositoryServiceRector instead.
 */
final class ModelGetRepositoryToRepositoryServiceRector extends AbstractRector
{
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

        $hasChanged = false;

        foreach ($getRepositoryCalls as $getRepositoryCall) {
            $repositoryClass = $this->resolveRepositoryClass($getRepositoryCall);
            if (null === $repositoryClass) {
                continue;
            }

            $propertyName = $this->resolvePropertyName($repositoryClass);

            // The model reaching its own repository already holds the property - just swap the call for it.
            if (!$this->isCallOnThis($getRepositoryCall)) {
                $this->classDependencyManipulator->addConstructorDependency(
                    $node,
                    new PropertyMetadata($propertyName, new ObjectType($repositoryClass))
                );
            }

            $this->replaceNode($node, $getRepositoryCall, new PropertyFetch(new Variable('this'), $propertyName));
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
     * as the generic Doctrine EntityRepository, in which case there is no dedicated service to depend on.
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

    private function isCallOnThis(MethodCall $methodCall): bool
    {
        return $methodCall->var instanceof Variable && $this->isName($methodCall->var, 'this');
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
