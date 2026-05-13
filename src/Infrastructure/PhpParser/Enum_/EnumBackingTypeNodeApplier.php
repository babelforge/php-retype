<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRetype\Infrastructure\PhpParser\Enum_;

use PhpNoobs\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnostic;
use PhpNoobs\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnosticSeverity;
use PhpNoobs\PhpRetype\Domain\Retype\Operation\RetypeOperation;
use PhpNoobs\PhpRetype\Domain\Retype\Target\RetypeTargetKind;
use PhpNoobs\PhpRetype\Infrastructure\PhpParser\Application\RetypeApplicationContext;
use PhpNoobs\PhpRetype\Infrastructure\PhpParser\Application\RetypeNodeApplierInterface;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\Enum_;

/**
 * Applies enum backing type changes to enum declaration nodes.
 */
final readonly class EnumBackingTypeNodeApplier implements RetypeNodeApplierInterface
{
    /**
     * Indicates whether this applier supports the retype operation.
     *
     * @param RetypeOperation $operation the retype operation to inspect
     */
    public function supports(RetypeOperation $operation): bool
    {
        return RetypeTargetKind::ENUM_BACKING === $operation->targetKind;
    }

    /**
     * Applies one enum backing type change operation.
     *
     * @param RetypeOperation          $operation the retype operation to apply
     * @param RetypeApplicationContext $context   the retype application context
     */
    public function apply(RetypeOperation $operation, RetypeApplicationContext $context): bool
    {
        $node = $operation->node;

        if (!$node instanceof Enum_) {
            $context->diagnostics->add(new RetypeDiagnostic(
                severity: RetypeDiagnosticSeverity::WARNING,
                message: sprintf('Unsupported enum backing retype node "%s".', $node::class),
            ));

            return false;
        }

        if (!$operation->typeNode instanceof Identifier) {
            $context->diagnostics->add(new RetypeDiagnostic(
                severity: RetypeDiagnosticSeverity::WARNING,
                message: 'Unsupported enum backing retype type node.',
            ));

            return false;
        }

        $node->scalarType = clone $operation->typeNode;

        return true;
    }
}
