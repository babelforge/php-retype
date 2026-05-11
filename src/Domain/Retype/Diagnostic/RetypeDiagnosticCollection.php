<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRetype\Domain\Retype\Diagnostic;

/**
 * Collection of retype diagnostics.
 *
 * @implements \IteratorAggregate<RetypeDiagnostic>
 */
final class RetypeDiagnosticCollection implements \Countable, \IteratorAggregate
{
    /**
     * @var list<RetypeDiagnostic>
     */
    private array $diagnostics = [];

    /**
     * Adds a diagnostic.
     *
     * @param RetypeDiagnostic $diagnostic the diagnostic to add
     */
    public function add(RetypeDiagnostic $diagnostic): self
    {
        $this->diagnostics[] = $diagnostic;

        return $this;
    }

    /**
     * Creates an empty diagnostic collection.
     */
    public static function empty(): self
    {
        return new self();
    }

    /**
     * Indicates whether the collection contains at least one error diagnostic.
     */
    public function hasErrors(): bool
    {
        foreach ($this->diagnostics as $diagnostic) {
            if (RetypeDiagnosticSeverity::ERROR === $diagnostic->severity) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the collection iterator.
     *
     * @return \Traversable<RetypeDiagnostic>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->diagnostics;
    }

    /**
     * Counts diagnostics.
     */
    public function count(): int
    {
        return count($this->diagnostics);
    }
}
