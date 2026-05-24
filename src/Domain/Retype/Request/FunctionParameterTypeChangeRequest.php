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
 * Describes a function parameter type change intent.
 */
final readonly class FunctionParameterTypeChangeRequest implements RetypeRequestInterface
{
    /**
     * Constructor.
     *
     * @param string                                                       $functionName   the fully-qualified function name
     * @param string                                                       $parameterName  the parameter name without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode       the native PHP type node to write
     * @param string|null                                                  $docType        the PHPDoc type to write in the `@param` tag
     * @param int|null                                                     $parameterIndex the optional zero-based declaration index
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function __construct(
        public string $functionName,
        public string $parameterName,
        public Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        public ?string $docType = null,
        public ?int $parameterIndex = null,
    ) {
        RetypeInputValidator::guardFqcn($functionName, 'functionName');
        RetypeInputValidator::guardShortIdentifier($parameterName, 'parameterName');
        RetypeInputValidator::guardParameterIndex($parameterIndex, 'parameterIndex');
        RetypeInputValidator::guardParameterNativeType($typeNode);
        RetypeInputValidator::guardDocType($docType, 'docType');
    }
}
