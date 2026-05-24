<?php

declare(strict_types=1);

namespace BabelForge\PhpRetype\Infrastructure\PhpParser\Method;

use BabelForge\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnostic;
use BabelForge\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnosticSeverity;
use BabelForge\PhpRetype\Domain\Retype\Operation\RetypeOperation;
use BabelForge\PhpRetype\Domain\Retype\Target\RetypeTargetKind;
use BabelForge\PhpRetype\Infrastructure\PhpParser\Application\RetypeApplicationContext;
use BabelForge\PhpRetype\Infrastructure\PhpParser\Application\RetypeNodeApplierInterface;
use PhpParser\Node\Stmt\ClassMethod;

/**
 * Applies method return type changes to method declaration nodes.
 */
final readonly class MethodReturnTypeNodeApplier implements RetypeNodeApplierInterface
{
    /**
     * Indicates whether this applier supports the retype operation.
     *
     * @param RetypeOperation $operation the retype operation to inspect
     */
    public function supports(RetypeOperation $operation): bool
    {
        return RetypeTargetKind::METHOD_RETURN === $operation->targetKind;
    }

    /**
     * Applies one method return type change operation.
     *
     * @param RetypeOperation          $operation the retype operation to apply
     * @param RetypeApplicationContext $context   the retype application context
     */
    public function apply(RetypeOperation $operation, RetypeApplicationContext $context): bool
    {
        $node = $operation->node;

        if (!$node instanceof ClassMethod) {
            $context->diagnostics->add(new RetypeDiagnostic(
                severity: RetypeDiagnosticSeverity::WARNING,
                message: sprintf('Unsupported method return retype node "%s".', $node::class),
            ));

            return false;
        }

        $node->returnType = null === $operation->typeNode ? null : clone $operation->typeNode;

        return true;
    }
}
