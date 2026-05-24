<?php

declare(strict_types=1);

namespace BabelForge\PhpRetype\Domain\Retype\Transaction;

use BabelForge\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use BabelForge\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnosticCollection;
use BabelForge\PhpRetype\Domain\Retype\Plan\RetypeResult;
use BabelForge\PhpSource\VirtualPhpSourceFileCollection;

/**
 * Describes the aggregate result of a retype transaction.
 */
final readonly class RetypeTransactionResult
{
    /**
     * Constructor.
     *
     * @param RetypeTransactionStatus        $status        the transaction status
     * @param list<RetypeResult>             $actionResults the individual retype action results
     * @param MemberDependencyGraphBuild     $finalBuild    the final member graph build
     * @param VirtualPhpSourceFileCollection $virtualFiles  the final virtual files
     * @param RetypeDiagnosticCollection     $diagnostics   the aggregate diagnostics
     */
    public function __construct(
        public RetypeTransactionStatus $status,
        public array $actionResults,
        public MemberDependencyGraphBuild $finalBuild,
        public VirtualPhpSourceFileCollection $virtualFiles,
        public RetypeDiagnosticCollection $diagnostics,
    ) {
    }
}
