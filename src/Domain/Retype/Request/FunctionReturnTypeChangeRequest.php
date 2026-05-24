<?php

declare(strict_types=1);

namespace BabelForge\PhpRetype\Domain\Retype\Request;

use BabelForge\PhpRetype\Domain\Retype\Validation\RetypeInputValidator;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\UnionType;

/**
 * Describes a function return type change intent.
 */
final readonly class FunctionReturnTypeChangeRequest implements RetypeRequestInterface
{
    /**
     * Constructor.
     *
     * @param string                                                       $functionName the fully-qualified function name
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode     the native PHP type node to write
     * @param string|null                                                  $docType      the PHPDoc type to write in the `@return` tag
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function __construct(
        public string $functionName,
        public Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        public ?string $docType = null,
    ) {
        RetypeInputValidator::guardFqcn($functionName, 'functionName');
        RetypeInputValidator::guardReturnNativeType($typeNode);
        RetypeInputValidator::guardDocType($docType, 'docType');
    }
}
