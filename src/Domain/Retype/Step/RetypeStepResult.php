<?php

declare(strict_types=1);

namespace BabelForge\PhpRetype\Domain\Retype\Step;

use BabelForge\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnosticCollection;
use BabelForge\PhpRetype\Domain\Retype\Plan\RetypePlan;
use BabelForge\PhpRetype\Domain\Retype\Plan\RetypeResult;
use BabelForge\PhpSource\VirtualPhpSourceFileCollection;

/**
 * Describes the outcome of one orchestrated retype step.
 */
final readonly class RetypeStepResult
{
    /**
     * Constructor.
     *
     * @param RetypeStepContext              $context              the next step context
     * @param RetypePlan                     $plan                 the executed retype plan
     * @param RetypeResult                   $retypeResult         the low-level retype application result
     * @param RetypeDiagnosticCollection     $diagnostics          the aggregated step diagnostics
     * @param VirtualPhpSourceFileCollection $touchedFiles         the virtual files targeted by the plan
     * @param bool                           $applied              whether at least one operation was applied
     * @param bool                           $requiresGraphRefresh whether the graph was refreshed after this step
     */
    public function __construct(
        public RetypeStepContext $context,
        public RetypePlan $plan,
        public RetypeResult $retypeResult,
        public RetypeDiagnosticCollection $diagnostics,
        public VirtualPhpSourceFileCollection $touchedFiles,
        public bool $applied,
        public bool $requiresGraphRefresh,
    ) {
    }
}
