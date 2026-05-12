<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRetype\Domain\Retype\Request;

use PhpNoobs\PhpRetype\Domain\Retype\Validation\RetypeInputValidator;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\UnionType;

/**
 * Describes a property type change intent.
 */
final readonly class PropertyTypeChangeRequest implements RetypeRequestInterface
{
    /**
     * @var list<string>
     */
    public array $propertyNames;

    /**
     * Constructor.
     *
     * @param string                                                       $className     the property owner FQCN
     * @param string|list<string>                                          $propertyNames the property name or property names without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode      the native PHP type node to write
     * @param string|null                                                  $docType       the PHPDoc type to write in the `@var` tag
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function __construct(
        public string $className,
        string|array $propertyNames,
        public Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        public ?string $docType = null,
    ) {
        RetypeInputValidator::guardFqcn($className, 'className');
        $this->propertyNames = $this->normalizePropertyNames($propertyNames);
        RetypeInputValidator::guardPropertyNativeType($typeNode);
        RetypeInputValidator::guardDocType($docType, 'docType');
    }

    /**
     * Normalizes one or more property names.
     *
     * @param string|list<string> $propertyNames the raw property names
     *
     * @return list<string>
     *
     * @throws \InvalidArgumentException when the property name list is empty or invalid
     */
    private function normalizePropertyNames(string|array $propertyNames): array
    {
        $names = [];

        foreach (is_string($propertyNames) ? [$propertyNames] : $propertyNames as $propertyName) {
            $names[] = $propertyName;
        }

        if ([] === $names) {
            throw new \InvalidArgumentException('The "propertyNames" retype input must contain at least one property name.');
        }

        foreach ($names as $name) {
            RetypeInputValidator::guardShortIdentifier($name, 'propertyNames');
        }

        $uniqueNames = [];

        foreach ($names as $name) {
            if (in_array($name, $uniqueNames, true)) {
                continue;
            }

            $uniqueNames[] = $name;
        }

        return $uniqueNames;
    }
}
