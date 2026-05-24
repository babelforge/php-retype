<?php

declare(strict_types=1);

namespace BabelForge\PhpRetype\Domain\Retype\Diagnostic;

/**
 * Reports one planning or application diagnostic for a retype operation.
 */
final readonly class RetypeDiagnostic
{
    /**
     * Constructor.
     *
     * @param RetypeDiagnosticSeverity $severity the diagnostic severity
     * @param string                   $message  the diagnostic message
     */
    public function __construct(
        public RetypeDiagnosticSeverity $severity,
        public string $message,
    ) {
    }
}
