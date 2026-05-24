<?php

declare(strict_types=1);

namespace BabelForge\PhpRetype\Infrastructure\PhpParser\Parameter;

use BabelForge\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnostic;
use BabelForge\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnosticSeverity;
use BabelForge\PhpRetype\Domain\Retype\Operation\RetypeOperation;
use BabelForge\PhpRetype\Domain\Retype\Target\RetypeTargetKind;
use BabelForge\PhpRetype\Infrastructure\PhpParser\Application\RetypeApplicationContext;
use BabelForge\PhpRetype\Infrastructure\PhpParser\Application\RetypeNodeApplierInterface;
use PhpParser\Node\Param;

/**
 * Applies parameter type changes to parameter declaration nodes.
 */
final readonly class ParameterTypeNodeApplier implements RetypeNodeApplierInterface
{
    /**
     * Indicates whether this applier supports the retype operation.
     *
     * @param RetypeOperation $operation the retype operation to inspect
     */
    public function supports(RetypeOperation $operation): bool
    {
        return RetypeTargetKind::METHOD_PARAMETER === $operation->targetKind
            || RetypeTargetKind::FUNCTION_PARAMETER === $operation->targetKind
            || RetypeTargetKind::CLOSURE_PARAMETER === $operation->targetKind
            || RetypeTargetKind::ARROW_FUNCTION_PARAMETER === $operation->targetKind;
    }

    /**
     * Applies one parameter type change operation.
     *
     * @param RetypeOperation          $operation the retype operation to apply
     * @param RetypeApplicationContext $context   the retype application context
     */
    public function apply(RetypeOperation $operation, RetypeApplicationContext $context): bool
    {
        $node = $operation->node;

        if (!$node instanceof Param) {
            $context->diagnostics->add(new RetypeDiagnostic(
                severity: RetypeDiagnosticSeverity::WARNING,
                message: sprintf('Unsupported parameter retype node "%s".', $node::class),
            ));

            return false;
        }

        $node->type = null === $operation->typeNode ? null : clone $operation->typeNode;

        return true;
    }
}
