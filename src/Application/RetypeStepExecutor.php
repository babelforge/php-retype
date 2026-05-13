<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRetype\Application;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphFactory;
use PhpNoobs\PhpRetype\Application\Contract\RetypePlanApplierInterface;
use PhpNoobs\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnosticCollection;
use PhpNoobs\PhpRetype\Domain\Retype\Plan\RetypePlan;
use PhpNoobs\PhpRetype\Domain\Retype\Plan\RetypeResult;
use PhpNoobs\PhpRetype\Domain\Retype\Step\RetypeStepContext;
use PhpNoobs\PhpRetype\Domain\Retype\Step\RetypeStepResult;
use PhpNoobs\PhpSource\VirtualPhpSourceFileCollection;

/**
 * Executes one retype plan against an orchestrable step context.
 */
final readonly class RetypeStepExecutor
{
    /**
     * Constructor.
     *
     * @param RetypePlanApplierInterface $retypePlanApplier the retype plan applier
     */
    public function __construct(private RetypePlanApplierInterface $retypePlanApplier)
    {
    }

    /**
     * Executes one planned retype step.
     *
     * @param RetypePlan        $plan    the retype plan to execute
     * @param RetypeStepContext $context the current step context
     */
    public function execute(RetypePlan $plan, RetypeStepContext $context): RetypeStepResult
    {
        $diagnostics = RetypeDiagnosticCollection::empty();
        $this->mergeDiagnostics($diagnostics, $plan->diagnostics);
        $touchedFiles = $this->touchedFiles($plan);

        if ($plan->diagnostics->hasErrors()) {
            return new RetypeStepResult(
                context: $context,
                plan: $plan,
                retypeResult: new RetypeResult($plan, $context->currentBuild->virtualFiles, RetypeDiagnosticCollection::empty()),
                diagnostics: $diagnostics,
                touchedFiles: $touchedFiles,
                applied: false,
                requiresGraphRefresh: false,
            );
        }

        $retypeResult = $this->retypePlanApplier->apply($plan, $context->currentBuild);
        $this->mergeDiagnostics($diagnostics, $retypeResult->diagnostics);

        if ($retypeResult->diagnostics->hasErrors()) {
            return new RetypeStepResult(
                context: $context,
                plan: $plan,
                retypeResult: $retypeResult,
                diagnostics: $diagnostics,
                touchedFiles: $touchedFiles,
                applied: false,
                requiresGraphRefresh: false,
            );
        }

        $applied = 0 < count($plan->operations);

        return new RetypeStepResult(
            context: $this->refreshContext($retypeResult, $context, $applied),
            plan: $plan,
            retypeResult: $retypeResult,
            diagnostics: $diagnostics,
            touchedFiles: $touchedFiles,
            applied: $applied,
            requiresGraphRefresh: $applied,
        );
    }

    /**
     * Refreshes the step context after one successful plan application.
     *
     * @param RetypeResult      $retypeResult the retype application result
     * @param RetypeStepContext $context      the previous step context
     * @param bool              $applied      whether the step applied at least one operation
     */
    private function refreshContext(
        RetypeResult $retypeResult,
        RetypeStepContext $context,
        bool $applied,
    ): RetypeStepContext {
        if (!$applied) {
            return $context;
        }

        return new RetypeStepContext(MemberDependencyGraphFactory::refreshFromTouchedVirtualFiles(
            previousBuild: $context->currentBuild,
            touchedVirtualFiles: $retypeResult->virtualFiles,
        ));
    }

    /**
     * Collects the virtual files touched by one plan.
     *
     * @param RetypePlan $plan the retype plan to inspect
     */
    private function touchedFiles(RetypePlan $plan): VirtualPhpSourceFileCollection
    {
        $touchedFiles = new VirtualPhpSourceFileCollection();

        foreach ($plan->operations as $operation) {
            if ($touchedFiles->has($operation->file->virtualFilePath)) {
                continue;
            }

            $touchedFiles->add($operation->file);
        }

        return $touchedFiles;
    }

    /**
     * Adds diagnostics from one collection to another.
     *
     * @param RetypeDiagnosticCollection $target the target diagnostics collection
     * @param RetypeDiagnosticCollection $source the source diagnostics collection
     */
    private function mergeDiagnostics(RetypeDiagnosticCollection $target, RetypeDiagnosticCollection $source): void
    {
        foreach ($source as $diagnostic) {
            $target->add($diagnostic);
        }
    }
}
