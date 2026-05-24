<?php

declare(strict_types=1);

namespace BabelForge\PhpRetype\Infrastructure\PhpParser\Function_;

use BabelForge\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnostic;
use BabelForge\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnosticSeverity;
use BabelForge\PhpRetype\Domain\Retype\Operation\RetypeOperation;
use BabelForge\PhpRetype\Domain\Retype\Target\RetypeTargetKind;
use BabelForge\PhpRetype\Infrastructure\PhpParser\Application\RetypeApplicationContext;
use BabelForge\PhpRetype\Infrastructure\PhpParser\Application\RetypeNodeApplierInterface;
use PhpParser\Node\Stmt\Function_;

/**
 * Applies function return type changes to function declaration nodes.
 */
final readonly class FunctionReturnTypeNodeApplier implements RetypeNodeApplierInterface
{
    /**
     * Indicates whether this applier supports the retype operation.
     *
     * @param RetypeOperation $operation the retype operation to inspect
     */
    public function supports(RetypeOperation $operation): bool
    {
        return RetypeTargetKind::FUNCTION_RETURN === $operation->targetKind;
    }

    /**
     * Applies one function return type change operation.
     *
     * @param RetypeOperation          $operation the retype operation to apply
     * @param RetypeApplicationContext $context   the retype application context
     */
    public function apply(RetypeOperation $operation, RetypeApplicationContext $context): bool
    {
        $node = $operation->node;

        if (!$node instanceof Function_) {
            $context->diagnostics->add(new RetypeDiagnostic(
                severity: RetypeDiagnosticSeverity::WARNING,
                message: sprintf('Unsupported function return retype node "%s".', $node::class),
            ));

            return false;
        }

        $node->returnType = null === $operation->typeNode ? null : clone $operation->typeNode;

        return true;
    }
}
