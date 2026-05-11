<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRetype\Domain\Retype\Validation;

use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\UnionType;

/**
 * Validates public retype inputs before planning starts.
 */
final readonly class RetypeInputValidator
{
    /**
     * Guards against instantiation.
     */
    private function __construct()
    {
    }

    /**
     * Validates a fully-qualified PHP symbol name.
     *
     * @param string $value the input value
     * @param string $name  the input name
     *
     * @throws \InvalidArgumentException when the value is not a FQCN-like name
     */
    public static function guardFqcn(string $value, string $name): void
    {
        if (1 !== preg_match('/^\\\\?[A-Za-z_][A-Za-z0-9_]*(?:\\\\[A-Za-z_][A-Za-z0-9_]*)*$/', $value)) {
            throw new \InvalidArgumentException(sprintf('The "%s" retype input must be a valid fully-qualified name.', $name));
        }
    }

    /**
     * Validates a short PHP identifier.
     *
     * @param string $value the input value
     * @param string $name  the input name
     *
     * @throws \InvalidArgumentException when the value is not a short identifier
     */
    public static function guardShortIdentifier(string $value, string $name): void
    {
        if (1 !== preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $value)) {
            throw new \InvalidArgumentException(sprintf('The "%s" retype input must be a valid short identifier.', $name));
        }
    }

    /**
     * Validates an optional zero-based parameter index.
     *
     * @param int|null $value the optional parameter index
     * @param string   $name  the input name
     *
     * @throws \InvalidArgumentException when the index is negative
     */
    public static function guardParameterIndex(?int $value, string $name): void
    {
        if (null !== $value && 0 > $value) {
            throw new \InvalidArgumentException(sprintf('The "%s" retype input must be greater than or equal to zero.', $name));
        }
    }

    /**
     * Validates an optional PHPDoc type string.
     *
     * @param string|null $value the optional PHPDoc type
     * @param string      $name  the input name
     *
     * @throws \InvalidArgumentException when the PHPDoc type is blank
     */
    public static function guardDocType(?string $value, string $name): void
    {
        if (null !== $value && '' === trim($value)) {
            throw new \InvalidArgumentException(sprintf('The "%s" retype input must not be blank when provided.', $name));
        }
    }

    /**
     * Validates a native type used on a parameter.
     *
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode the native type node
     *
     * @throws \InvalidArgumentException when the node is invalid for a parameter
     */
    public static function guardParameterNativeType(
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
    ): void {
        if (null === $typeNode) {
            return;
        }

        foreach (self::flattenTypeNodes($typeNode) as $node) {
            if (!$node instanceof Identifier) {
                continue;
            }

            $name = strtolower($node->name);

            if ('void' === $name || 'never' === $name) {
                throw new \InvalidArgumentException(sprintf('The native "%s" type is not valid for a parameter.', $name));
            }
        }
    }

    /**
     * Flattens a native type node into leaf nodes.
     *
     * @param Identifier|Name|NullableType|UnionType|IntersectionType $typeNode the native type node
     *
     * @return list<Node>
     */
    private static function flattenTypeNodes(Identifier|Name|NullableType|UnionType|IntersectionType $typeNode): array
    {
        if ($typeNode instanceof NullableType) {
            return self::flattenTypeNodes($typeNode->type);
        }

        if ($typeNode instanceof UnionType || $typeNode instanceof IntersectionType) {
            $nodes = [];

            foreach ($typeNode->types as $innerType) {
                array_push($nodes, ...self::flattenTypeNodes($innerType));
            }

            return $nodes;
        }

        return [$typeNode];
    }
}
