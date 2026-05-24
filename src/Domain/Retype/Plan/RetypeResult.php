<?php

declare(strict_types=1);

namespace BabelForge\PhpRetype\Domain\Retype\Plan;

use BabelForge\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnosticCollection;
use BabelForge\PhpSource\VirtualPhpSourceFileCollection;

/**
 * Describes the outcome of applying a retype plan.
 */
final readonly class RetypeResult
{
    /**
     * Constructor.
     *
     * @param RetypePlan                     $plan         the applied retype plan
     * @param VirtualPhpSourceFileCollection $virtualFiles the virtual files after AST mutation
     * @param RetypeDiagnosticCollection     $diagnostics  the application diagnostics
     */
    public function __construct(
        public RetypePlan $plan,
        public VirtualPhpSourceFileCollection $virtualFiles,
        public RetypeDiagnosticCollection $diagnostics,
    ) {
    }
}
