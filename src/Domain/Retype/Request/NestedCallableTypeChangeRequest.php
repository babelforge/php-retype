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
 * Describes a type change on a closure or arrow function found inside a container.
 */
final readonly class NestedCallableTypeChangeRequest implements RetypeRequestInterface
{
    /**
     * Constructor.
     *
     * @param NestedCallableContainerKind                                  $containerKind  the container kind
     * @param NestedCallableKind                                           $callableKind   the nested callable kind
     * @param NestedCallableTargetKind                                     $targetKind     the nested callable target kind
     * @param int                                                          $callableIndex  the zero-based callable index inside the container
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode       the native PHP type node to write
     * @param string|null                                                  $docType        the PHPDoc type to write
     * @param string|null                                                  $className      the method owner FQCN
     * @param string|null                                                  $methodName     the method name
     * @param string|null                                                  $functionName   the fully-qualified function name
     * @param string|null                                                  $filePath       the physical or virtual file path
     * @param string|null                                                  $parameterName  the parameter name without "$"
     * @param int|null                                                     $parameterIndex the optional zero-based parameter index
     *
     * @throws \InvalidArgumentException when one input is invalid
     */
    public function __construct(
        public NestedCallableContainerKind $containerKind,
        public NestedCallableKind $callableKind,
        public NestedCallableTargetKind $targetKind,
        public int $callableIndex,
        public Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        public ?string $docType = null,
        public ?string $className = null,
        public ?string $methodName = null,
        public ?string $functionName = null,
        public ?string $filePath = null,
        public ?string $parameterName = null,
        public ?int $parameterIndex = null,
    ) {
        RetypeInputValidator::guardNonNegativeIndex($callableIndex, 'callableIndex');
        RetypeInputValidator::guardDocType($docType, 'docType');

        if (NestedCallableTargetKind::PARAMETER === $targetKind) {
            RetypeInputValidator::guardParameterNativeType($typeNode);
            RetypeInputValidator::guardParameterIndex($parameterIndex, 'parameterIndex');

            if (null !== $parameterName) {
                RetypeInputValidator::guardShortIdentifier($parameterName, 'parameterName');
            }
        }

        if (NestedCallableTargetKind::RETURN === $targetKind) {
            RetypeInputValidator::guardReturnNativeType($typeNode);
        }

        if (NestedCallableContainerKind::METHOD === $containerKind) {
            RetypeInputValidator::guardFqcn((string) $className, 'className');
            RetypeInputValidator::guardShortIdentifier((string) $methodName, 'methodName');
        }

        if (NestedCallableContainerKind::FUNCTION === $containerKind) {
            RetypeInputValidator::guardFqcn((string) $functionName, 'functionName');
        }

        if (NestedCallableContainerKind::FILE === $containerKind && (null === $filePath || '' === trim($filePath))) {
            throw new \InvalidArgumentException('The "filePath" retype input must not be blank.');
        }
    }
}
