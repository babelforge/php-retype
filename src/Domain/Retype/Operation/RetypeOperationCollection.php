<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRetype\Domain\Retype\Operation;

/**
 * Collection of retype operations.
 *
 * @implements \IteratorAggregate<RetypeOperation>
 */
final class RetypeOperationCollection implements \Countable, \IteratorAggregate
{
    /**
     * @var list<RetypeOperation>
     */
    private array $operations = [];

    /**
     * Adds an operation.
     *
     * @param RetypeOperation $operation the operation to add
     */
    public function add(RetypeOperation $operation): self
    {
        $this->operations[] = $operation;

        return $this;
    }

    /**
     * Creates an empty operation collection.
     */
    public static function empty(): self
    {
        return new self();
    }

    /**
     * Returns the collection iterator.
     *
     * @return \Traversable<RetypeOperation>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->operations;
    }

    /**
     * Counts operations.
     */
    public function count(): int
    {
        return count($this->operations);
    }
}
