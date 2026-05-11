<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRetype\Domain\Retype\Plan;

use PhpNoobs\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnosticCollection;
use PhpNoobs\PhpSource\VirtualPhpSourceFileCollection;

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
