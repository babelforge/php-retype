<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRetype\Infrastructure\PhpParser\Application;

use PhpNoobs\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnosticCollection;

/**
 * Carries shared state while applying retype operations.
 */
final readonly class RetypeApplicationContext
{
    /**
     * Constructor.
     *
     * @param RetypeDiagnosticCollection $diagnostics the diagnostics collected during retype application
     */
    public function __construct(
        public RetypeDiagnosticCollection $diagnostics,
    ) {
    }
}
