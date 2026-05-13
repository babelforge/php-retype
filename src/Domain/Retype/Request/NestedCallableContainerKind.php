<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRetype\Domain\Retype\Request;

/**
 * Identifies the container used to find a nested callable.
 */
enum NestedCallableContainerKind
{
    case METHOD;
    case FUNCTION;
    case FILE;
}
