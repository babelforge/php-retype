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
 * Describes a method return type change intent.
 */
final readonly class MethodReturnTypeChangeRequest implements RetypeRequestInterface
{
    /**
     * Constructor.
     *
     * @param string                                                       $className  the method owner FQCN
     * @param string                                                       $methodName the method name
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode   the native PHP type node to write
     * @param string|null                                                  $docType    the PHPDoc type to write in the `@return` tag
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function __construct(
        public string $className,
        public string $methodName,
        public Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        public ?string $docType = null,
    ) {
        RetypeInputValidator::guardFqcn($className, 'className');
        RetypeInputValidator::guardShortIdentifier($methodName, 'methodName');
        RetypeInputValidator::guardReturnNativeType($typeNode);
        RetypeInputValidator::guardDocType($docType, 'docType');
    }
}
