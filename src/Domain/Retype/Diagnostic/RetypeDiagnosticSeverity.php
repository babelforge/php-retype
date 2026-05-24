<?php

declare(strict_types=1);

namespace BabelForge\PhpRetype\Domain\Retype\Diagnostic;

/**
 * Identifies the severity of a retype diagnostic.
 */
enum RetypeDiagnosticSeverity: string
{
    case INFO = 'info';
    case WARNING = 'warning';
    case ERROR = 'error';
}
