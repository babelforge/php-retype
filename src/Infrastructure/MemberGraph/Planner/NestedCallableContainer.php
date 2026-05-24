<?php

declare(strict_types=1);

namespace BabelForge\PhpRetype\Infrastructure\MemberGraph\Planner;

use BabelForge\PhpSource\VirtualPhpSourceFile;
use PhpParser\Node;

/**
 * Carries a nested callable search container and its source file.
 *
 * @internal
 */
final readonly class NestedCallableContainer
{
    /**
     * Constructor.
     *
     * @param VirtualPhpSourceFile $file the virtual source file
     * @param Node|list<Node>      $node the container node or file-level nodes
     */
    public function __construct(
        public VirtualPhpSourceFile $file,
        public Node|array $node,
    ) {
    }
}
