<?php

declare(strict_types=1);

namespace BabelForge\PhpRetype\Application;

use BabelForge\PhpRetype\Domain\Retype\Plan\RetypeResult;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\UnionType;

/**
 * Provides explicit transaction methods for closure and arrow-function type changes.
 */
trait NestedCallablePhpRetypeTransactionMethods
{
    /**
     * Plans and applies a closure parameter type change inside a method within the transaction.
     *
     * @param string                                                       $className      the method owner FQCN
     * @param string                                                       $methodName     the method name
     * @param int                                                          $closureIndex   the zero-based closure index
     * @param string                                                       $parameterName  the parameter name without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode       the native PHP type node to write
     * @param string|null                                                  $docType        the PHPDoc type to write in the `@param` tag
     * @param int|null                                                     $parameterIndex the optional zero-based parameter index
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     * @throws \LogicException           when the transaction is no longer active
     */
    public function changeClosureParameterTypeInMethod(
        string $className,
        string $methodName,
        int $closureIndex,
        string $parameterName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
        ?int $parameterIndex = null,
    ): RetypeResult {
        return $this->execute($this->retyper()->planClosureParameterTypeInMethod(
            className: $className,
            methodName: $methodName,
            closureIndex: $closureIndex,
            parameterName: $parameterName,
            typeNode: $typeNode,
            docType: $docType,
            parameterIndex: $parameterIndex,
        ));
    }

    /**
     * Plans and applies a closure return type change inside a method within the transaction.
     *
     * @param string                                                       $className    the method owner FQCN
     * @param string                                                       $methodName   the method name
     * @param int                                                          $closureIndex the zero-based closure index
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode     the native PHP type node to write
     * @param string|null                                                  $docType      the PHPDoc type to write in the `@return` tag
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     * @throws \LogicException           when the transaction is no longer active
     */
    public function changeClosureReturnTypeInMethod(
        string $className,
        string $methodName,
        int $closureIndex,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): RetypeResult {
        return $this->execute($this->retyper()->planClosureReturnTypeInMethod(
            className: $className,
            methodName: $methodName,
            closureIndex: $closureIndex,
            typeNode: $typeNode,
            docType: $docType,
        ));
    }

    /**
     * Plans and applies an arrow function parameter type change inside a method within the transaction.
     *
     * @param string                                                       $className          the method owner FQCN
     * @param string                                                       $methodName         the method name
     * @param int                                                          $arrowFunctionIndex the zero-based arrow-function index
     * @param string                                                       $parameterName      the parameter name without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode           the native PHP type node to write
     * @param string|null                                                  $docType            the PHPDoc type to write in the `@param` tag
     * @param int|null                                                     $parameterIndex     the optional zero-based parameter index
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     * @throws \LogicException           when the transaction is no longer active
     */
    public function changeArrowFunctionParameterTypeInMethod(
        string $className,
        string $methodName,
        int $arrowFunctionIndex,
        string $parameterName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
        ?int $parameterIndex = null,
    ): RetypeResult {
        return $this->execute($this->retyper()->planArrowFunctionParameterTypeInMethod(
            className: $className,
            methodName: $methodName,
            arrowFunctionIndex: $arrowFunctionIndex,
            parameterName: $parameterName,
            typeNode: $typeNode,
            docType: $docType,
            parameterIndex: $parameterIndex,
        ));
    }

    /**
     * Plans and applies an arrow function return type change inside a method within the transaction.
     *
     * @param string                                                       $className          the method owner FQCN
     * @param string                                                       $methodName         the method name
     * @param int                                                          $arrowFunctionIndex the zero-based arrow-function index
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode           the native PHP type node to write
     * @param string|null                                                  $docType            the PHPDoc type to write in the `@return` tag
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     * @throws \LogicException           when the transaction is no longer active
     */
    public function changeArrowFunctionReturnTypeInMethod(
        string $className,
        string $methodName,
        int $arrowFunctionIndex,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): RetypeResult {
        return $this->execute($this->retyper()->planArrowFunctionReturnTypeInMethod(
            className: $className,
            methodName: $methodName,
            arrowFunctionIndex: $arrowFunctionIndex,
            typeNode: $typeNode,
            docType: $docType,
        ));
    }

    /**
     * Plans and applies a closure parameter type change inside a function within the transaction.
     *
     * @param string                                                       $functionName   the fully-qualified function name
     * @param int                                                          $closureIndex   the zero-based closure index
     * @param string                                                       $parameterName  the parameter name without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode       the native PHP type node to write
     * @param string|null                                                  $docType        the PHPDoc type to write in the `@param` tag
     * @param int|null                                                     $parameterIndex the optional zero-based parameter index
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     * @throws \LogicException           when the transaction is no longer active
     */
    public function changeClosureParameterTypeInFunction(
        string $functionName,
        int $closureIndex,
        string $parameterName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
        ?int $parameterIndex = null,
    ): RetypeResult {
        return $this->execute($this->retyper()->planClosureParameterTypeInFunction(
            functionName: $functionName,
            closureIndex: $closureIndex,
            parameterName: $parameterName,
            typeNode: $typeNode,
            docType: $docType,
            parameterIndex: $parameterIndex,
        ));
    }

    /**
     * Plans and applies a closure return type change inside a function within the transaction.
     *
     * @param string                                                       $functionName the fully-qualified function name
     * @param int                                                          $closureIndex the zero-based closure index
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode     the native PHP type node to write
     * @param string|null                                                  $docType      the PHPDoc type to write in the `@return` tag
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     * @throws \LogicException           when the transaction is no longer active
     */
    public function changeClosureReturnTypeInFunction(
        string $functionName,
        int $closureIndex,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): RetypeResult {
        return $this->execute($this->retyper()->planClosureReturnTypeInFunction(
            functionName: $functionName,
            closureIndex: $closureIndex,
            typeNode: $typeNode,
            docType: $docType,
        ));
    }

    /**
     * Plans and applies an arrow function parameter type change inside a function within the transaction.
     *
     * @param string                                                       $functionName       the fully-qualified function name
     * @param int                                                          $arrowFunctionIndex the zero-based arrow-function index
     * @param string                                                       $parameterName      the parameter name without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode           the native PHP type node to write
     * @param string|null                                                  $docType            the PHPDoc type to write in the `@param` tag
     * @param int|null                                                     $parameterIndex     the optional zero-based parameter index
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     * @throws \LogicException           when the transaction is no longer active
     */
    public function changeArrowFunctionParameterTypeInFunction(
        string $functionName,
        int $arrowFunctionIndex,
        string $parameterName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
        ?int $parameterIndex = null,
    ): RetypeResult {
        return $this->execute($this->retyper()->planArrowFunctionParameterTypeInFunction(
            functionName: $functionName,
            arrowFunctionIndex: $arrowFunctionIndex,
            parameterName: $parameterName,
            typeNode: $typeNode,
            docType: $docType,
            parameterIndex: $parameterIndex,
        ));
    }

    /**
     * Plans and applies an arrow function return type change inside a function within the transaction.
     *
     * @param string                                                       $functionName       the fully-qualified function name
     * @param int                                                          $arrowFunctionIndex the zero-based arrow-function index
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode           the native PHP type node to write
     * @param string|null                                                  $docType            the PHPDoc type to write in the `@return` tag
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     * @throws \LogicException           when the transaction is no longer active
     */
    public function changeArrowFunctionReturnTypeInFunction(
        string $functionName,
        int $arrowFunctionIndex,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): RetypeResult {
        return $this->execute($this->retyper()->planArrowFunctionReturnTypeInFunction(
            functionName: $functionName,
            arrowFunctionIndex: $arrowFunctionIndex,
            typeNode: $typeNode,
            docType: $docType,
        ));
    }

    /**
     * Plans and applies a closure parameter type change inside a file within the transaction.
     *
     * @param string                                                       $filePath       the physical or virtual file path
     * @param int                                                          $closureIndex   the zero-based closure index
     * @param string                                                       $parameterName  the parameter name without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode       the native PHP type node to write
     * @param string|null                                                  $docType        the PHPDoc type to write in the `@param` tag
     * @param int|null                                                     $parameterIndex the optional zero-based parameter index
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     * @throws \LogicException           when the transaction is no longer active
     */
    public function changeClosureParameterTypeInFile(
        string $filePath,
        int $closureIndex,
        string $parameterName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
        ?int $parameterIndex = null,
    ): RetypeResult {
        return $this->execute($this->retyper()->planClosureParameterTypeInFile(
            filePath: $filePath,
            closureIndex: $closureIndex,
            parameterName: $parameterName,
            typeNode: $typeNode,
            docType: $docType,
            parameterIndex: $parameterIndex,
        ));
    }

    /**
     * Plans and applies a closure return type change inside a file within the transaction.
     *
     * @param string                                                       $filePath     the physical or virtual file path
     * @param int                                                          $closureIndex the zero-based closure index
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode     the native PHP type node to write
     * @param string|null                                                  $docType      the PHPDoc type to write in the `@return` tag
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     * @throws \LogicException           when the transaction is no longer active
     */
    public function changeClosureReturnTypeInFile(
        string $filePath,
        int $closureIndex,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): RetypeResult {
        return $this->execute($this->retyper()->planClosureReturnTypeInFile(
            filePath: $filePath,
            closureIndex: $closureIndex,
            typeNode: $typeNode,
            docType: $docType,
        ));
    }

    /**
     * Plans and applies an arrow function parameter type change inside a file within the transaction.
     *
     * @param string                                                       $filePath           the physical or virtual file path
     * @param int                                                          $arrowFunctionIndex the zero-based arrow-function index
     * @param string                                                       $parameterName      the parameter name without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode           the native PHP type node to write
     * @param string|null                                                  $docType            the PHPDoc type to write in the `@param` tag
     * @param int|null                                                     $parameterIndex     the optional zero-based parameter index
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     * @throws \LogicException           when the transaction is no longer active
     */
    public function changeArrowFunctionParameterTypeInFile(
        string $filePath,
        int $arrowFunctionIndex,
        string $parameterName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
        ?int $parameterIndex = null,
    ): RetypeResult {
        return $this->execute($this->retyper()->planArrowFunctionParameterTypeInFile(
            filePath: $filePath,
            arrowFunctionIndex: $arrowFunctionIndex,
            parameterName: $parameterName,
            typeNode: $typeNode,
            docType: $docType,
            parameterIndex: $parameterIndex,
        ));
    }

    /**
     * Plans and applies an arrow function return type change inside a file within the transaction.
     *
     * @param string                                                       $filePath           the physical or virtual file path
     * @param int                                                          $arrowFunctionIndex the zero-based arrow-function index
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode           the native PHP type node to write
     * @param string|null                                                  $docType            the PHPDoc type to write in the `@return` tag
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     * @throws \LogicException           when the transaction is no longer active
     */
    public function changeArrowFunctionReturnTypeInFile(
        string $filePath,
        int $arrowFunctionIndex,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): RetypeResult {
        return $this->execute($this->retyper()->planArrowFunctionReturnTypeInFile(
            filePath: $filePath,
            arrowFunctionIndex: $arrowFunctionIndex,
            typeNode: $typeNode,
            docType: $docType,
        ));
    }
}
