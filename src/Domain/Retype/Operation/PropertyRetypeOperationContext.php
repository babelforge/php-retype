<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRetype\Domain\Retype\Operation;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;

/**
 * Carries structural AST context needed to retype a property declaration.
 */
final readonly class PropertyRetypeOperationContext
{
    /**
     * Constructor.
     *
     * @param ClassLike|null $parentClassLike              the class-like node containing the property declaration
     * @param int|null       $parentPropertyStatementIndex the grouped property statement index in the class-like node
     * @param list<string>   $targetPropertyNames          the property names targeted by this operation
     * @param bool           $allSiblingsTargeted          whether the full grouped declaration is targeted
     * @param Node|null      $phpDocOwner                  the direct PHPDoc owner when unambiguous
     */
    public function __construct(
        public ?ClassLike $parentClassLike,
        public ?int $parentPropertyStatementIndex,
        public array $targetPropertyNames,
        public bool $allSiblingsTargeted,
        public ?Node $phpDocOwner,
    ) {
    }

    /**
     * Indicates whether one property name is targeted.
     *
     * @param string $propertyName the property name
     */
    public function targets(string $propertyName): bool
    {
        return in_array($propertyName, $this->targetPropertyNames, true);
    }
}
