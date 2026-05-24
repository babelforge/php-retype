<?php

declare(strict_types=1);

namespace BabelForge\PhpRetype\Infrastructure\PhpParser\Application;

use BabelForge\PhpRetype\Domain\Retype\Operation\RetypeOperation;

/**
 * Applies metadata changes associated with supported retype operations.
 */
interface RetypeMetadataApplierInterface
{
    /**
     * Indicates whether this applier supports the retype operation.
     *
     * @param RetypeOperation $operation the retype operation to inspect
     */
    public function supports(RetypeOperation $operation): bool;

    /**
     * Applies metadata changes for one retype operation.
     *
     * @param RetypeOperation          $operation the retype operation to apply
     * @param RetypeApplicationContext $context   the retype application context
     */
    public function apply(RetypeOperation $operation, RetypeApplicationContext $context): void;
}
