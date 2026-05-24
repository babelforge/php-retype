<?php

declare(strict_types=1);

namespace BabelForge\PhpRetype\Domain\Retype\Operation;

/**
 * Identifies why one AST node participates in a retype plan.
 */
enum RetypeOperationRole: string
{
    case DECLARATION = 'declaration';
    case PHPDOC = 'phpdoc';
}
