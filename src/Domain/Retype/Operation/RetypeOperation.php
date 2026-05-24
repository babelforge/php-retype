<?php

declare(strict_types=1);

namespace BabelForge\PhpRetype\Domain\Retype\Operation;

use BabelForge\PhpRetype\Domain\Retype\Target\RetypeTargetKind;
use BabelForge\PhpSource\VirtualPhpSourceFile;
use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\UnionType;

/**
 * Describes one AST type mutation.
 */
final readonly class RetypeOperation
{
    /**
     * Constructor.
     *
     * @param RetypeTargetKind                                             $targetKind      the retyped target kind
     * @param RetypeOperationRole                                          $role            the operation role in the plan
     * @param VirtualPhpSourceFile                                         $file            the virtual source file containing the node
     * @param Node                                                         $node            the AST node to mutate
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode        the native PHP type node to write
     * @param string|null                                                  $docType         the PHPDoc type to write when relevant
     * @param PropertyRetypeOperationContext|null                          $propertyContext the optional property-specific structural context
     */
    public function __construct(
        public RetypeTargetKind $targetKind,
        public RetypeOperationRole $role,
        public VirtualPhpSourceFile $file,
        public Node $node,
        public Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        public ?string $docType,
        public ?PropertyRetypeOperationContext $propertyContext = null,
    ) {
    }
}
