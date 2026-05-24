<?php

declare(strict_types=1);

namespace BabelForge\PhpRetype\Domain\Retype\Transaction;

/**
 * Enumerates retype transaction statuses.
 */
enum RetypeTransactionStatus
{
    case ACTIVE;
    case FAILED;
    case COMMITTED;
    case ROLLED_BACK;
}
