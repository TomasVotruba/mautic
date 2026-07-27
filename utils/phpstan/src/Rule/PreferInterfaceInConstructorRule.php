<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * A constructor dependency must be autowired by its interface, not by the concrete class.
 *
 * Typing "Router $router" locks the service to one implementation and blocks decoration, while
 * "RouterInterface $router" describes what the class actually needs. The interface is discovered by
 * convention: a param typed "X" is reported when "XInterface" exists and "X" implements it.
 *
 * @implements Rule<ClassMethod>
 */
final class PreferInterfaceInConstructorRule implements Rule
{
    /**
     * @var string[]
     */
    private const SKIPPED_INTERFACES = [
        // the concrete Session is the only way to reach getFlashBag()
        'Symfony\\Component\\HttpFoundation\\Session\\SessionInterface',
    ];

    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
    ) {
    }

    public function getNodeType(): string
    {
        return ClassMethod::class;
    }

    /**
     * @param ClassMethod $node
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ('__construct' !== $node->name->toLowerString()) {
            return [];
        }

        $ruleErrors = [];

        foreach ($node->params as $param) {
            if (!$param->type instanceof Name) {
                continue;
            }

            $className     = $scope->resolveName($param->type);
            $interfaceName = $this->matchImplementedSameNamedInterface($className);
            if (null === $interfaceName) {
                continue;
            }

            $parameterName = $param->var instanceof Node\Expr\Variable && is_string($param->var->name)
                ? '$'.$param->var->name
                : '';

            $ruleErrors[] = RuleErrorBuilder::message(sprintf(
                'Constructor dependency "%s" is typed as concrete "%s". Use the "%s" interface instead.',
                $parameterName,
                $className,
                $interfaceName
            ))
                ->identifier('mautic.preferInterfaceInConstructor')
                ->line($param->getStartLine())
                ->build();
        }

        return $ruleErrors;
    }

    /**
     * Returns the "<class>Interface" name when the class implements it, null otherwise.
     */
    private function matchImplementedSameNamedInterface(string $className): ?string
    {
        if (!$this->reflectionProvider->hasClass($className)) {
            return null;
        }

        $classReflection = $this->reflectionProvider->getClass($className);
        if ($classReflection->isInterface()) {
            return null;
        }

        $interfaceName = $className.'Interface';
        if (in_array($interfaceName, self::SKIPPED_INTERFACES, true)) {
            return null;
        }

        if (!$this->reflectionProvider->hasClass($interfaceName)) {
            return null;
        }

        if (!$this->reflectionProvider->getClass($interfaceName)->isInterface()) {
            return null;
        }

        if (!$classReflection->implementsInterface($interfaceName)) {
            return null;
        }

        return $interfaceName;
    }
}
