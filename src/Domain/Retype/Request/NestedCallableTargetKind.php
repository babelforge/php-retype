<?php

declare(strict_types=1);

namespace BabelForge\PhpRetype\Domain\Retype\Request;

/**
 * Identifies the type slot to mutate on a nested callable.
 */
enum NestedCallableTargetKind
{
    case PARAMETER;
    case RETURN;
}
