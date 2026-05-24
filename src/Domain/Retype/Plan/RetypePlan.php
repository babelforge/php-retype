<?php

declare(strict_types=1);

namespace BabelForge\PhpRetype\Domain\Retype\Plan;

use BabelForge\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnosticCollection;
use BabelForge\PhpRetype\Domain\Retype\Operation\RetypeOperationCollection;
use BabelForge\PhpRetype\Domain\Retype\Request\RetypeRequestInterface;

/**
 * Describes all AST operations and diagnostics for a retype request.
 */
final readonly class RetypePlan
{
    /**
     * Constructor.
     *
     * @param RetypeRequestInterface     $request     the retype request
     * @param RetypeOperationCollection  $operations  the AST retype operations
     * @param RetypeDiagnosticCollection $diagnostics the planning diagnostics
     */
    public function __construct(
        public RetypeRequestInterface $request,
        public RetypeOperationCollection $operations,
        public RetypeDiagnosticCollection $diagnostics,
    ) {
    }
}
