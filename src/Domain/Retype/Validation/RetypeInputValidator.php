<?php

declare(strict_types=1);

namespace BabelForge\PhpRetype\Domain\Retype\Validation;

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
     * Validates a zero-based index.
     *
     * @param int    $value the index
     * @param string $name  the input name
     *
     * @throws \InvalidArgumentException when the index is negative
     */
    public static function guardNonNegativeIndex(int $value, string $name): void
    {
        if (0 > $value) {
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
        self::guardNonVoidNonNeverNativeType($typeNode, 'parameter');
    }

    /**
     * Validates a native type used on a property.
     *
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode the native type node
     *
     * @throws \InvalidArgumentException when the node is invalid for a property
     */
    public static function guardPropertyNativeType(
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
    ): void {
        self::guardNonVoidNonNeverNativeType($typeNode, 'property');
    }

    /**
     * Validates a native type used on a class constant.
     *
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode the native type node
     *
     * @throws \InvalidArgumentException when the node is invalid for a class constant
     */
    public static function guardClassConstantNativeType(
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
    ): void {
        self::guardNonVoidNonNeverNativeType($typeNode, 'class constant');
    }

    /**
     * Validates a native type used as an enum backing type.
     *
     * @param Identifier $typeNode the native enum backing type node
     *
     * @throws \InvalidArgumentException when the node is invalid for an enum backing type
     */
    public static function guardEnumBackingNativeType(Identifier $typeNode): void
    {
        $name = strtolower($typeNode->name);

        if ('int' !== $name && 'string' !== $name) {
            throw new \InvalidArgumentException(sprintf('The native "%s" type is not valid for an enum backing type.', $name));
        }
    }

    /**
     * Validates a native type that cannot contain void or never.
     *
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode the native type node
     * @param string                                                       $target   the target name used in exception messages
     *
     * @throws \InvalidArgumentException when the node is invalid for the target
     */
    private static function guardNonVoidNonNeverNativeType(
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        string $target,
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
                throw new \InvalidArgumentException(sprintf('The native "%s" type is not valid for a %s.', $name, $target));
            }
        }
    }

    /**
     * Validates a native type used as a return type.
     *
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode the native type node
     *
     * @throws \InvalidArgumentException when the node is invalid for a return type
     */
    public static function guardReturnNativeType(
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
    ): void {
        if (null === $typeNode) {
            return;
        }

        if ($typeNode instanceof NullableType) {
            $innerName = self::nativeIdentifierName($typeNode->type);

            if ('void' === $innerName || 'never' === $innerName || 'mixed' === $innerName) {
                throw new \InvalidArgumentException(sprintf('The nullable "%s" return type is not valid.', $innerName));
            }

            return;
        }

        if ($typeNode instanceof UnionType) {
            foreach ($typeNode->types as $innerType) {
                $innerName = self::nativeIdentifierName($innerType);

                if ('void' === $innerName || 'never' === $innerName) {
                    throw new \InvalidArgumentException(sprintf('The native "%s" return type cannot be part of a union.', $innerName));
                }
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

    /**
     * Returns the lower-case native identifier name for a type node when available.
     *
     * @param Identifier|Name|NullableType|UnionType|IntersectionType $typeNode the native type node
     */
    private static function nativeIdentifierName(Identifier|Name|NullableType|UnionType|IntersectionType $typeNode): ?string
    {
        if (!$typeNode instanceof Identifier) {
            return null;
        }

        return strtolower($typeNode->name);
    }
}
