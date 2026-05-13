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
 * Describes a class constant type change intent.
 */
final readonly class ClassConstantTypeChangeRequest implements RetypeRequestInterface
{
    /**
     * Constructor.
     *
     * @param string                                                       $className    the class-like owner FQCN
     * @param string                                                       $constantName the class constant name
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode     the native PHP type node to write
     * @param string|null                                                  $docType      the PHPDoc type to write in the `@var` tag
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function __construct(
        public string $className,
        public string $constantName,
        public Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        public ?string $docType = null,
    ) {
        RetypeInputValidator::guardFqcn($className, 'className');
        RetypeInputValidator::guardShortIdentifier($constantName, 'constantName');
        RetypeInputValidator::guardClassConstantNativeType($typeNode);
        RetypeInputValidator::guardDocType($docType, 'docType');
    }
}
