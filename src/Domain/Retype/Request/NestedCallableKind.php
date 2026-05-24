<?php

declare(strict_types=1);

namespace BabelForge\PhpRetype\Domain\Retype\Request;

/**
 * Identifies the nested callable kind to retype.
 */
enum NestedCallableKind
{
    case CLOSURE;
    case ARROW_FUNCTION;
}
