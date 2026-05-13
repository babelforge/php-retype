<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRetype\Application;

use PhpNoobs\PhpRetype\Domain\Retype\Plan\RetypePlan;
use PhpNoobs\PhpRetype\Domain\Retype\Plan\RetypeResult;
use PhpNoobs\PhpRetype\Domain\Retype\Request\NestedCallableContainerKind;
use PhpNoobs\PhpRetype\Domain\Retype\Request\NestedCallableKind;
use PhpNoobs\PhpRetype\Domain\Retype\Request\NestedCallableTargetKind;
use PhpNoobs\PhpRetype\Domain\Retype\Request\NestedCallableTypeChangeRequest;
use PhpNoobs\PhpRetype\Domain\Retype\Step\RetypeStepContext;
use PhpNoobs\PhpRetype\Domain\Retype\Step\RetypeStepResult;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\UnionType;

/**
 * Provides explicit public methods for closure and arrow-function type changes.
 */
trait NestedCallablePhpRetypeMethods
{
    /**
     * Executes one orchestrable closure parameter type change inside a method.
     *
     * @param RetypeStepContext                                            $context        the current retype step context
     * @param string                                                       $className      the method owner FQCN
     * @param string                                                       $methodName     the method name
     * @param int                                                          $closureIndex   the zero-based closure index
     * @param string                                                       $parameterName  the parameter name without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode       the native PHP type node to write
     * @param string|null                                                  $docType        the PHPDoc type to write in the `@param` tag
     * @param int|null                                                     $parameterIndex the optional zero-based parameter index
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function executeStepClosureParameterTypeInMethod(
        RetypeStepContext $context,
        string $className,
        string $methodName,
        int $closureIndex,
        string $parameterName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
        ?int $parameterIndex = null,
    ): RetypeStepResult {
        return $this->executeStepNestedCallableTypeChange($context, new NestedCallableTypeChangeRequest(
            containerKind: NestedCallableContainerKind::METHOD,
            callableKind: NestedCallableKind::CLOSURE,
            targetKind: NestedCallableTargetKind::PARAMETER,
            callableIndex: $closureIndex,
            typeNode: $typeNode,
            docType: $docType,
            className: $className,
            methodName: $methodName,
            parameterName: $parameterName,
            parameterIndex: $parameterIndex,
        ));
    }

    /**
     * Plans a closure parameter type change inside a method.
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
     */
    public function planClosureParameterTypeInMethod(
        string $className,
        string $methodName,
        int $closureIndex,
        string $parameterName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
        ?int $parameterIndex = null,
    ): RetypePlan {
        return $this->planNestedCallableTypeChange(new NestedCallableTypeChangeRequest(
            containerKind: NestedCallableContainerKind::METHOD,
            callableKind: NestedCallableKind::CLOSURE,
            targetKind: NestedCallableTargetKind::PARAMETER,
            callableIndex: $closureIndex,
            typeNode: $typeNode,
            docType: $docType,
            className: $className,
            methodName: $methodName,
            parameterName: $parameterName,
            parameterIndex: $parameterIndex,
        ));
    }

    /**
     * Plans and applies a closure parameter type change inside a method.
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
        return $this->changeNestedCallableType(new NestedCallableTypeChangeRequest(
            containerKind: NestedCallableContainerKind::METHOD,
            callableKind: NestedCallableKind::CLOSURE,
            targetKind: NestedCallableTargetKind::PARAMETER,
            callableIndex: $closureIndex,
            typeNode: $typeNode,
            docType: $docType,
            className: $className,
            methodName: $methodName,
            parameterName: $parameterName,
            parameterIndex: $parameterIndex,
        ));
    }

    /**
     * Executes one orchestrable closure return type change inside a method.
     *
     * @param RetypeStepContext                                            $context      the current retype step context
     * @param string                                                       $className    the method owner FQCN
     * @param string                                                       $methodName   the method name
     * @param int                                                          $closureIndex the zero-based closure index
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode     the native PHP type node to write
     * @param string|null                                                  $docType      the PHPDoc type to write in the `@return` tag
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function executeStepClosureReturnTypeInMethod(
        RetypeStepContext $context,
        string $className,
        string $methodName,
        int $closureIndex,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): RetypeStepResult {
        return $this->executeStepNestedCallableTypeChange($context, new NestedCallableTypeChangeRequest(
            containerKind: NestedCallableContainerKind::METHOD,
            callableKind: NestedCallableKind::CLOSURE,
            targetKind: NestedCallableTargetKind::RETURN,
            callableIndex: $closureIndex,
            typeNode: $typeNode,
            docType: $docType,
            className: $className,
            methodName: $methodName,
            parameterName: null,
            parameterIndex: null,
        ));
    }

    /**
     * Plans a closure return type change inside a method.
     *
     * @param string                                                       $className    the method owner FQCN
     * @param string                                                       $methodName   the method name
     * @param int                                                          $closureIndex the zero-based closure index
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode     the native PHP type node to write
     * @param string|null                                                  $docType      the PHPDoc type to write in the `@return` tag
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function planClosureReturnTypeInMethod(
        string $className,
        string $methodName,
        int $closureIndex,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): RetypePlan {
        return $this->planNestedCallableTypeChange(new NestedCallableTypeChangeRequest(
            containerKind: NestedCallableContainerKind::METHOD,
            callableKind: NestedCallableKind::CLOSURE,
            targetKind: NestedCallableTargetKind::RETURN,
            callableIndex: $closureIndex,
            typeNode: $typeNode,
            docType: $docType,
            className: $className,
            methodName: $methodName,
            parameterName: null,
            parameterIndex: null,
        ));
    }

    /**
     * Plans and applies a closure return type change inside a method.
     *
     * @param string                                                       $className    the method owner FQCN
     * @param string                                                       $methodName   the method name
     * @param int                                                          $closureIndex the zero-based closure index
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode     the native PHP type node to write
     * @param string|null                                                  $docType      the PHPDoc type to write in the `@return` tag
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function changeClosureReturnTypeInMethod(
        string $className,
        string $methodName,
        int $closureIndex,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): RetypeResult {
        return $this->changeNestedCallableType(new NestedCallableTypeChangeRequest(
            containerKind: NestedCallableContainerKind::METHOD,
            callableKind: NestedCallableKind::CLOSURE,
            targetKind: NestedCallableTargetKind::RETURN,
            callableIndex: $closureIndex,
            typeNode: $typeNode,
            docType: $docType,
            className: $className,
            methodName: $methodName,
            parameterName: null,
            parameterIndex: null,
        ));
    }

    /**
     * Executes one orchestrable arrow function parameter type change inside a method.
     *
     * @param RetypeStepContext                                            $context            the current retype step context
     * @param string                                                       $className          the method owner FQCN
     * @param string                                                       $methodName         the method name
     * @param int                                                          $arrowFunctionIndex the zero-based arrow-function index
     * @param string                                                       $parameterName      the parameter name without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode           the native PHP type node to write
     * @param string|null                                                  $docType            the PHPDoc type to write in the `@param` tag
     * @param int|null                                                     $parameterIndex     the optional zero-based parameter index
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function executeStepArrowFunctionParameterTypeInMethod(
        RetypeStepContext $context,
        string $className,
        string $methodName,
        int $arrowFunctionIndex,
        string $parameterName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
        ?int $parameterIndex = null,
    ): RetypeStepResult {
        return $this->executeStepNestedCallableTypeChange($context, new NestedCallableTypeChangeRequest(
            containerKind: NestedCallableContainerKind::METHOD,
            callableKind: NestedCallableKind::ARROW_FUNCTION,
            targetKind: NestedCallableTargetKind::PARAMETER,
            callableIndex: $arrowFunctionIndex,
            typeNode: $typeNode,
            docType: $docType,
            className: $className,
            methodName: $methodName,
            parameterName: $parameterName,
            parameterIndex: $parameterIndex,
        ));
    }

    /**
     * Plans an arrow function parameter type change inside a method.
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
     */
    public function planArrowFunctionParameterTypeInMethod(
        string $className,
        string $methodName,
        int $arrowFunctionIndex,
        string $parameterName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
        ?int $parameterIndex = null,
    ): RetypePlan {
        return $this->planNestedCallableTypeChange(new NestedCallableTypeChangeRequest(
            containerKind: NestedCallableContainerKind::METHOD,
            callableKind: NestedCallableKind::ARROW_FUNCTION,
            targetKind: NestedCallableTargetKind::PARAMETER,
            callableIndex: $arrowFunctionIndex,
            typeNode: $typeNode,
            docType: $docType,
            className: $className,
            methodName: $methodName,
            parameterName: $parameterName,
            parameterIndex: $parameterIndex,
        ));
    }

    /**
     * Plans and applies an arrow function parameter type change inside a method.
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
        return $this->changeNestedCallableType(new NestedCallableTypeChangeRequest(
            containerKind: NestedCallableContainerKind::METHOD,
            callableKind: NestedCallableKind::ARROW_FUNCTION,
            targetKind: NestedCallableTargetKind::PARAMETER,
            callableIndex: $arrowFunctionIndex,
            typeNode: $typeNode,
            docType: $docType,
            className: $className,
            methodName: $methodName,
            parameterName: $parameterName,
            parameterIndex: $parameterIndex,
        ));
    }

    /**
     * Executes one orchestrable arrow function return type change inside a method.
     *
     * @param RetypeStepContext                                            $context            the current retype step context
     * @param string                                                       $className          the method owner FQCN
     * @param string                                                       $methodName         the method name
     * @param int                                                          $arrowFunctionIndex the zero-based arrow-function index
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode           the native PHP type node to write
     * @param string|null                                                  $docType            the PHPDoc type to write in the `@return` tag
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function executeStepArrowFunctionReturnTypeInMethod(
        RetypeStepContext $context,
        string $className,
        string $methodName,
        int $arrowFunctionIndex,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): RetypeStepResult {
        return $this->executeStepNestedCallableTypeChange($context, new NestedCallableTypeChangeRequest(
            containerKind: NestedCallableContainerKind::METHOD,
            callableKind: NestedCallableKind::ARROW_FUNCTION,
            targetKind: NestedCallableTargetKind::RETURN,
            callableIndex: $arrowFunctionIndex,
            typeNode: $typeNode,
            docType: $docType,
            className: $className,
            methodName: $methodName,
            parameterName: null,
            parameterIndex: null,
        ));
    }

    /**
     * Plans an arrow function return type change inside a method.
     *
     * @param string                                                       $className          the method owner FQCN
     * @param string                                                       $methodName         the method name
     * @param int                                                          $arrowFunctionIndex the zero-based arrow-function index
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode           the native PHP type node to write
     * @param string|null                                                  $docType            the PHPDoc type to write in the `@return` tag
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function planArrowFunctionReturnTypeInMethod(
        string $className,
        string $methodName,
        int $arrowFunctionIndex,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): RetypePlan {
        return $this->planNestedCallableTypeChange(new NestedCallableTypeChangeRequest(
            containerKind: NestedCallableContainerKind::METHOD,
            callableKind: NestedCallableKind::ARROW_FUNCTION,
            targetKind: NestedCallableTargetKind::RETURN,
            callableIndex: $arrowFunctionIndex,
            typeNode: $typeNode,
            docType: $docType,
            className: $className,
            methodName: $methodName,
            parameterName: null,
            parameterIndex: null,
        ));
    }

    /**
     * Plans and applies an arrow function return type change inside a method.
     *
     * @param string                                                       $className          the method owner FQCN
     * @param string                                                       $methodName         the method name
     * @param int                                                          $arrowFunctionIndex the zero-based arrow-function index
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode           the native PHP type node to write
     * @param string|null                                                  $docType            the PHPDoc type to write in the `@return` tag
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function changeArrowFunctionReturnTypeInMethod(
        string $className,
        string $methodName,
        int $arrowFunctionIndex,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): RetypeResult {
        return $this->changeNestedCallableType(new NestedCallableTypeChangeRequest(
            containerKind: NestedCallableContainerKind::METHOD,
            callableKind: NestedCallableKind::ARROW_FUNCTION,
            targetKind: NestedCallableTargetKind::RETURN,
            callableIndex: $arrowFunctionIndex,
            typeNode: $typeNode,
            docType: $docType,
            className: $className,
            methodName: $methodName,
            parameterName: null,
            parameterIndex: null,
        ));
    }

    /**
     * Executes one orchestrable closure parameter type change inside a function.
     *
     * @param RetypeStepContext                                            $context        the current retype step context
     * @param string                                                       $functionName   the fully-qualified function name
     * @param int                                                          $closureIndex   the zero-based closure index
     * @param string                                                       $parameterName  the parameter name without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode       the native PHP type node to write
     * @param string|null                                                  $docType        the PHPDoc type to write in the `@param` tag
     * @param int|null                                                     $parameterIndex the optional zero-based parameter index
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function executeStepClosureParameterTypeInFunction(
        RetypeStepContext $context,
        string $functionName,
        int $closureIndex,
        string $parameterName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
        ?int $parameterIndex = null,
    ): RetypeStepResult {
        return $this->executeStepNestedCallableTypeChange($context, new NestedCallableTypeChangeRequest(
            containerKind: NestedCallableContainerKind::FUNCTION,
            callableKind: NestedCallableKind::CLOSURE,
            targetKind: NestedCallableTargetKind::PARAMETER,
            callableIndex: $closureIndex,
            typeNode: $typeNode,
            docType: $docType,
            functionName: $functionName,
            parameterName: $parameterName,
            parameterIndex: $parameterIndex,
        ));
    }

    /**
     * Plans a closure parameter type change inside a function.
     *
     * @param string                                                       $functionName   the fully-qualified function name
     * @param int                                                          $closureIndex   the zero-based closure index
     * @param string                                                       $parameterName  the parameter name without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode       the native PHP type node to write
     * @param string|null                                                  $docType        the PHPDoc type to write in the `@param` tag
     * @param int|null                                                     $parameterIndex the optional zero-based parameter index
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function planClosureParameterTypeInFunction(
        string $functionName,
        int $closureIndex,
        string $parameterName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
        ?int $parameterIndex = null,
    ): RetypePlan {
        return $this->planNestedCallableTypeChange(new NestedCallableTypeChangeRequest(
            containerKind: NestedCallableContainerKind::FUNCTION,
            callableKind: NestedCallableKind::CLOSURE,
            targetKind: NestedCallableTargetKind::PARAMETER,
            callableIndex: $closureIndex,
            typeNode: $typeNode,
            docType: $docType,
            functionName: $functionName,
            parameterName: $parameterName,
            parameterIndex: $parameterIndex,
        ));
    }

    /**
     * Plans and applies a closure parameter type change inside a function.
     *
     * @param string                                                       $functionName   the fully-qualified function name
     * @param int                                                          $closureIndex   the zero-based closure index
     * @param string                                                       $parameterName  the parameter name without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode       the native PHP type node to write
     * @param string|null                                                  $docType        the PHPDoc type to write in the `@param` tag
     * @param int|null                                                     $parameterIndex the optional zero-based parameter index
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function changeClosureParameterTypeInFunction(
        string $functionName,
        int $closureIndex,
        string $parameterName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
        ?int $parameterIndex = null,
    ): RetypeResult {
        return $this->changeNestedCallableType(new NestedCallableTypeChangeRequest(
            containerKind: NestedCallableContainerKind::FUNCTION,
            callableKind: NestedCallableKind::CLOSURE,
            targetKind: NestedCallableTargetKind::PARAMETER,
            callableIndex: $closureIndex,
            typeNode: $typeNode,
            docType: $docType,
            functionName: $functionName,
            parameterName: $parameterName,
            parameterIndex: $parameterIndex,
        ));
    }

    /**
     * Executes one orchestrable closure return type change inside a function.
     *
     * @param RetypeStepContext                                            $context      the current retype step context
     * @param string                                                       $functionName the fully-qualified function name
     * @param int                                                          $closureIndex the zero-based closure index
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode     the native PHP type node to write
     * @param string|null                                                  $docType      the PHPDoc type to write in the `@return` tag
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function executeStepClosureReturnTypeInFunction(
        RetypeStepContext $context,
        string $functionName,
        int $closureIndex,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): RetypeStepResult {
        return $this->executeStepNestedCallableTypeChange($context, new NestedCallableTypeChangeRequest(
            containerKind: NestedCallableContainerKind::FUNCTION,
            callableKind: NestedCallableKind::CLOSURE,
            targetKind: NestedCallableTargetKind::RETURN,
            callableIndex: $closureIndex,
            typeNode: $typeNode,
            docType: $docType,
            functionName: $functionName,
            parameterName: null,
            parameterIndex: null,
        ));
    }

    /**
     * Plans a closure return type change inside a function.
     *
     * @param string                                                       $functionName the fully-qualified function name
     * @param int                                                          $closureIndex the zero-based closure index
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode     the native PHP type node to write
     * @param string|null                                                  $docType      the PHPDoc type to write in the `@return` tag
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function planClosureReturnTypeInFunction(
        string $functionName,
        int $closureIndex,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): RetypePlan {
        return $this->planNestedCallableTypeChange(new NestedCallableTypeChangeRequest(
            containerKind: NestedCallableContainerKind::FUNCTION,
            callableKind: NestedCallableKind::CLOSURE,
            targetKind: NestedCallableTargetKind::RETURN,
            callableIndex: $closureIndex,
            typeNode: $typeNode,
            docType: $docType,
            functionName: $functionName,
            parameterName: null,
            parameterIndex: null,
        ));
    }

    /**
     * Plans and applies a closure return type change inside a function.
     *
     * @param string                                                       $functionName the fully-qualified function name
     * @param int                                                          $closureIndex the zero-based closure index
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode     the native PHP type node to write
     * @param string|null                                                  $docType      the PHPDoc type to write in the `@return` tag
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function changeClosureReturnTypeInFunction(
        string $functionName,
        int $closureIndex,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): RetypeResult {
        return $this->changeNestedCallableType(new NestedCallableTypeChangeRequest(
            containerKind: NestedCallableContainerKind::FUNCTION,
            callableKind: NestedCallableKind::CLOSURE,
            targetKind: NestedCallableTargetKind::RETURN,
            callableIndex: $closureIndex,
            typeNode: $typeNode,
            docType: $docType,
            functionName: $functionName,
            parameterName: null,
            parameterIndex: null,
        ));
    }

    /**
     * Executes one orchestrable arrow function parameter type change inside a function.
     *
     * @param RetypeStepContext                                            $context            the current retype step context
     * @param string                                                       $functionName       the fully-qualified function name
     * @param int                                                          $arrowFunctionIndex the zero-based arrow-function index
     * @param string                                                       $parameterName      the parameter name without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode           the native PHP type node to write
     * @param string|null                                                  $docType            the PHPDoc type to write in the `@param` tag
     * @param int|null                                                     $parameterIndex     the optional zero-based parameter index
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function executeStepArrowFunctionParameterTypeInFunction(
        RetypeStepContext $context,
        string $functionName,
        int $arrowFunctionIndex,
        string $parameterName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
        ?int $parameterIndex = null,
    ): RetypeStepResult {
        return $this->executeStepNestedCallableTypeChange($context, new NestedCallableTypeChangeRequest(
            containerKind: NestedCallableContainerKind::FUNCTION,
            callableKind: NestedCallableKind::ARROW_FUNCTION,
            targetKind: NestedCallableTargetKind::PARAMETER,
            callableIndex: $arrowFunctionIndex,
            typeNode: $typeNode,
            docType: $docType,
            functionName: $functionName,
            parameterName: $parameterName,
            parameterIndex: $parameterIndex,
        ));
    }

    /**
     * Plans an arrow function parameter type change inside a function.
     *
     * @param string                                                       $functionName       the fully-qualified function name
     * @param int                                                          $arrowFunctionIndex the zero-based arrow-function index
     * @param string                                                       $parameterName      the parameter name without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode           the native PHP type node to write
     * @param string|null                                                  $docType            the PHPDoc type to write in the `@param` tag
     * @param int|null                                                     $parameterIndex     the optional zero-based parameter index
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function planArrowFunctionParameterTypeInFunction(
        string $functionName,
        int $arrowFunctionIndex,
        string $parameterName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
        ?int $parameterIndex = null,
    ): RetypePlan {
        return $this->planNestedCallableTypeChange(new NestedCallableTypeChangeRequest(
            containerKind: NestedCallableContainerKind::FUNCTION,
            callableKind: NestedCallableKind::ARROW_FUNCTION,
            targetKind: NestedCallableTargetKind::PARAMETER,
            callableIndex: $arrowFunctionIndex,
            typeNode: $typeNode,
            docType: $docType,
            functionName: $functionName,
            parameterName: $parameterName,
            parameterIndex: $parameterIndex,
        ));
    }

    /**
     * Plans and applies an arrow function parameter type change inside a function.
     *
     * @param string                                                       $functionName       the fully-qualified function name
     * @param int                                                          $arrowFunctionIndex the zero-based arrow-function index
     * @param string                                                       $parameterName      the parameter name without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode           the native PHP type node to write
     * @param string|null                                                  $docType            the PHPDoc type to write in the `@param` tag
     * @param int|null                                                     $parameterIndex     the optional zero-based parameter index
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function changeArrowFunctionParameterTypeInFunction(
        string $functionName,
        int $arrowFunctionIndex,
        string $parameterName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
        ?int $parameterIndex = null,
    ): RetypeResult {
        return $this->changeNestedCallableType(new NestedCallableTypeChangeRequest(
            containerKind: NestedCallableContainerKind::FUNCTION,
            callableKind: NestedCallableKind::ARROW_FUNCTION,
            targetKind: NestedCallableTargetKind::PARAMETER,
            callableIndex: $arrowFunctionIndex,
            typeNode: $typeNode,
            docType: $docType,
            functionName: $functionName,
            parameterName: $parameterName,
            parameterIndex: $parameterIndex,
        ));
    }

    /**
     * Executes one orchestrable arrow function return type change inside a function.
     *
     * @param RetypeStepContext                                            $context            the current retype step context
     * @param string                                                       $functionName       the fully-qualified function name
     * @param int                                                          $arrowFunctionIndex the zero-based arrow-function index
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode           the native PHP type node to write
     * @param string|null                                                  $docType            the PHPDoc type to write in the `@return` tag
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function executeStepArrowFunctionReturnTypeInFunction(
        RetypeStepContext $context,
        string $functionName,
        int $arrowFunctionIndex,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): RetypeStepResult {
        return $this->executeStepNestedCallableTypeChange($context, new NestedCallableTypeChangeRequest(
            containerKind: NestedCallableContainerKind::FUNCTION,
            callableKind: NestedCallableKind::ARROW_FUNCTION,
            targetKind: NestedCallableTargetKind::RETURN,
            callableIndex: $arrowFunctionIndex,
            typeNode: $typeNode,
            docType: $docType,
            functionName: $functionName,
            parameterName: null,
            parameterIndex: null,
        ));
    }

    /**
     * Plans an arrow function return type change inside a function.
     *
     * @param string                                                       $functionName       the fully-qualified function name
     * @param int                                                          $arrowFunctionIndex the zero-based arrow-function index
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode           the native PHP type node to write
     * @param string|null                                                  $docType            the PHPDoc type to write in the `@return` tag
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function planArrowFunctionReturnTypeInFunction(
        string $functionName,
        int $arrowFunctionIndex,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): RetypePlan {
        return $this->planNestedCallableTypeChange(new NestedCallableTypeChangeRequest(
            containerKind: NestedCallableContainerKind::FUNCTION,
            callableKind: NestedCallableKind::ARROW_FUNCTION,
            targetKind: NestedCallableTargetKind::RETURN,
            callableIndex: $arrowFunctionIndex,
            typeNode: $typeNode,
            docType: $docType,
            functionName: $functionName,
            parameterName: null,
            parameterIndex: null,
        ));
    }

    /**
     * Plans and applies an arrow function return type change inside a function.
     *
     * @param string                                                       $functionName       the fully-qualified function name
     * @param int                                                          $arrowFunctionIndex the zero-based arrow-function index
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode           the native PHP type node to write
     * @param string|null                                                  $docType            the PHPDoc type to write in the `@return` tag
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function changeArrowFunctionReturnTypeInFunction(
        string $functionName,
        int $arrowFunctionIndex,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): RetypeResult {
        return $this->changeNestedCallableType(new NestedCallableTypeChangeRequest(
            containerKind: NestedCallableContainerKind::FUNCTION,
            callableKind: NestedCallableKind::ARROW_FUNCTION,
            targetKind: NestedCallableTargetKind::RETURN,
            callableIndex: $arrowFunctionIndex,
            typeNode: $typeNode,
            docType: $docType,
            functionName: $functionName,
            parameterName: null,
            parameterIndex: null,
        ));
    }

    /**
     * Executes one orchestrable closure parameter type change inside a file.
     *
     * @param RetypeStepContext                                            $context        the current retype step context
     * @param string                                                       $filePath       the physical or virtual file path
     * @param int                                                          $closureIndex   the zero-based closure index
     * @param string                                                       $parameterName  the parameter name without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode       the native PHP type node to write
     * @param string|null                                                  $docType        the PHPDoc type to write in the `@param` tag
     * @param int|null                                                     $parameterIndex the optional zero-based parameter index
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function executeStepClosureParameterTypeInFile(
        RetypeStepContext $context,
        string $filePath,
        int $closureIndex,
        string $parameterName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
        ?int $parameterIndex = null,
    ): RetypeStepResult {
        return $this->executeStepNestedCallableTypeChange($context, new NestedCallableTypeChangeRequest(
            containerKind: NestedCallableContainerKind::FILE,
            callableKind: NestedCallableKind::CLOSURE,
            targetKind: NestedCallableTargetKind::PARAMETER,
            callableIndex: $closureIndex,
            typeNode: $typeNode,
            docType: $docType,
            filePath: $filePath,
            parameterName: $parameterName,
            parameterIndex: $parameterIndex,
        ));
    }

    /**
     * Plans a closure parameter type change inside a file.
     *
     * @param string                                                       $filePath       the physical or virtual file path
     * @param int                                                          $closureIndex   the zero-based closure index
     * @param string                                                       $parameterName  the parameter name without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode       the native PHP type node to write
     * @param string|null                                                  $docType        the PHPDoc type to write in the `@param` tag
     * @param int|null                                                     $parameterIndex the optional zero-based parameter index
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function planClosureParameterTypeInFile(
        string $filePath,
        int $closureIndex,
        string $parameterName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
        ?int $parameterIndex = null,
    ): RetypePlan {
        return $this->planNestedCallableTypeChange(new NestedCallableTypeChangeRequest(
            containerKind: NestedCallableContainerKind::FILE,
            callableKind: NestedCallableKind::CLOSURE,
            targetKind: NestedCallableTargetKind::PARAMETER,
            callableIndex: $closureIndex,
            typeNode: $typeNode,
            docType: $docType,
            filePath: $filePath,
            parameterName: $parameterName,
            parameterIndex: $parameterIndex,
        ));
    }

    /**
     * Plans and applies a closure parameter type change inside a file.
     *
     * @param string                                                       $filePath       the physical or virtual file path
     * @param int                                                          $closureIndex   the zero-based closure index
     * @param string                                                       $parameterName  the parameter name without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode       the native PHP type node to write
     * @param string|null                                                  $docType        the PHPDoc type to write in the `@param` tag
     * @param int|null                                                     $parameterIndex the optional zero-based parameter index
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function changeClosureParameterTypeInFile(
        string $filePath,
        int $closureIndex,
        string $parameterName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
        ?int $parameterIndex = null,
    ): RetypeResult {
        return $this->changeNestedCallableType(new NestedCallableTypeChangeRequest(
            containerKind: NestedCallableContainerKind::FILE,
            callableKind: NestedCallableKind::CLOSURE,
            targetKind: NestedCallableTargetKind::PARAMETER,
            callableIndex: $closureIndex,
            typeNode: $typeNode,
            docType: $docType,
            filePath: $filePath,
            parameterName: $parameterName,
            parameterIndex: $parameterIndex,
        ));
    }

    /**
     * Executes one orchestrable closure return type change inside a file.
     *
     * @param RetypeStepContext                                            $context      the current retype step context
     * @param string                                                       $filePath     the physical or virtual file path
     * @param int                                                          $closureIndex the zero-based closure index
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode     the native PHP type node to write
     * @param string|null                                                  $docType      the PHPDoc type to write in the `@return` tag
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function executeStepClosureReturnTypeInFile(
        RetypeStepContext $context,
        string $filePath,
        int $closureIndex,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): RetypeStepResult {
        return $this->executeStepNestedCallableTypeChange($context, new NestedCallableTypeChangeRequest(
            containerKind: NestedCallableContainerKind::FILE,
            callableKind: NestedCallableKind::CLOSURE,
            targetKind: NestedCallableTargetKind::RETURN,
            callableIndex: $closureIndex,
            typeNode: $typeNode,
            docType: $docType,
            filePath: $filePath,
            parameterName: null,
            parameterIndex: null,
        ));
    }

    /**
     * Plans a closure return type change inside a file.
     *
     * @param string                                                       $filePath     the physical or virtual file path
     * @param int                                                          $closureIndex the zero-based closure index
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode     the native PHP type node to write
     * @param string|null                                                  $docType      the PHPDoc type to write in the `@return` tag
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function planClosureReturnTypeInFile(
        string $filePath,
        int $closureIndex,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): RetypePlan {
        return $this->planNestedCallableTypeChange(new NestedCallableTypeChangeRequest(
            containerKind: NestedCallableContainerKind::FILE,
            callableKind: NestedCallableKind::CLOSURE,
            targetKind: NestedCallableTargetKind::RETURN,
            callableIndex: $closureIndex,
            typeNode: $typeNode,
            docType: $docType,
            filePath: $filePath,
            parameterName: null,
            parameterIndex: null,
        ));
    }

    /**
     * Plans and applies a closure return type change inside a file.
     *
     * @param string                                                       $filePath     the physical or virtual file path
     * @param int                                                          $closureIndex the zero-based closure index
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode     the native PHP type node to write
     * @param string|null                                                  $docType      the PHPDoc type to write in the `@return` tag
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function changeClosureReturnTypeInFile(
        string $filePath,
        int $closureIndex,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): RetypeResult {
        return $this->changeNestedCallableType(new NestedCallableTypeChangeRequest(
            containerKind: NestedCallableContainerKind::FILE,
            callableKind: NestedCallableKind::CLOSURE,
            targetKind: NestedCallableTargetKind::RETURN,
            callableIndex: $closureIndex,
            typeNode: $typeNode,
            docType: $docType,
            filePath: $filePath,
            parameterName: null,
            parameterIndex: null,
        ));
    }

    /**
     * Executes one orchestrable arrow function parameter type change inside a file.
     *
     * @param RetypeStepContext                                            $context            the current retype step context
     * @param string                                                       $filePath           the physical or virtual file path
     * @param int                                                          $arrowFunctionIndex the zero-based arrow-function index
     * @param string                                                       $parameterName      the parameter name without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode           the native PHP type node to write
     * @param string|null                                                  $docType            the PHPDoc type to write in the `@param` tag
     * @param int|null                                                     $parameterIndex     the optional zero-based parameter index
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function executeStepArrowFunctionParameterTypeInFile(
        RetypeStepContext $context,
        string $filePath,
        int $arrowFunctionIndex,
        string $parameterName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
        ?int $parameterIndex = null,
    ): RetypeStepResult {
        return $this->executeStepNestedCallableTypeChange($context, new NestedCallableTypeChangeRequest(
            containerKind: NestedCallableContainerKind::FILE,
            callableKind: NestedCallableKind::ARROW_FUNCTION,
            targetKind: NestedCallableTargetKind::PARAMETER,
            callableIndex: $arrowFunctionIndex,
            typeNode: $typeNode,
            docType: $docType,
            filePath: $filePath,
            parameterName: $parameterName,
            parameterIndex: $parameterIndex,
        ));
    }

    /**
     * Plans an arrow function parameter type change inside a file.
     *
     * @param string                                                       $filePath           the physical or virtual file path
     * @param int                                                          $arrowFunctionIndex the zero-based arrow-function index
     * @param string                                                       $parameterName      the parameter name without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode           the native PHP type node to write
     * @param string|null                                                  $docType            the PHPDoc type to write in the `@param` tag
     * @param int|null                                                     $parameterIndex     the optional zero-based parameter index
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function planArrowFunctionParameterTypeInFile(
        string $filePath,
        int $arrowFunctionIndex,
        string $parameterName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
        ?int $parameterIndex = null,
    ): RetypePlan {
        return $this->planNestedCallableTypeChange(new NestedCallableTypeChangeRequest(
            containerKind: NestedCallableContainerKind::FILE,
            callableKind: NestedCallableKind::ARROW_FUNCTION,
            targetKind: NestedCallableTargetKind::PARAMETER,
            callableIndex: $arrowFunctionIndex,
            typeNode: $typeNode,
            docType: $docType,
            filePath: $filePath,
            parameterName: $parameterName,
            parameterIndex: $parameterIndex,
        ));
    }

    /**
     * Plans and applies an arrow function parameter type change inside a file.
     *
     * @param string                                                       $filePath           the physical or virtual file path
     * @param int                                                          $arrowFunctionIndex the zero-based arrow-function index
     * @param string                                                       $parameterName      the parameter name without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode           the native PHP type node to write
     * @param string|null                                                  $docType            the PHPDoc type to write in the `@param` tag
     * @param int|null                                                     $parameterIndex     the optional zero-based parameter index
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function changeArrowFunctionParameterTypeInFile(
        string $filePath,
        int $arrowFunctionIndex,
        string $parameterName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
        ?int $parameterIndex = null,
    ): RetypeResult {
        return $this->changeNestedCallableType(new NestedCallableTypeChangeRequest(
            containerKind: NestedCallableContainerKind::FILE,
            callableKind: NestedCallableKind::ARROW_FUNCTION,
            targetKind: NestedCallableTargetKind::PARAMETER,
            callableIndex: $arrowFunctionIndex,
            typeNode: $typeNode,
            docType: $docType,
            filePath: $filePath,
            parameterName: $parameterName,
            parameterIndex: $parameterIndex,
        ));
    }

    /**
     * Executes one orchestrable arrow function return type change inside a file.
     *
     * @param RetypeStepContext                                            $context            the current retype step context
     * @param string                                                       $filePath           the physical or virtual file path
     * @param int                                                          $arrowFunctionIndex the zero-based arrow-function index
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode           the native PHP type node to write
     * @param string|null                                                  $docType            the PHPDoc type to write in the `@return` tag
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function executeStepArrowFunctionReturnTypeInFile(
        RetypeStepContext $context,
        string $filePath,
        int $arrowFunctionIndex,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): RetypeStepResult {
        return $this->executeStepNestedCallableTypeChange($context, new NestedCallableTypeChangeRequest(
            containerKind: NestedCallableContainerKind::FILE,
            callableKind: NestedCallableKind::ARROW_FUNCTION,
            targetKind: NestedCallableTargetKind::RETURN,
            callableIndex: $arrowFunctionIndex,
            typeNode: $typeNode,
            docType: $docType,
            filePath: $filePath,
            parameterName: null,
            parameterIndex: null,
        ));
    }

    /**
     * Plans an arrow function return type change inside a file.
     *
     * @param string                                                       $filePath           the physical or virtual file path
     * @param int                                                          $arrowFunctionIndex the zero-based arrow-function index
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode           the native PHP type node to write
     * @param string|null                                                  $docType            the PHPDoc type to write in the `@return` tag
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function planArrowFunctionReturnTypeInFile(
        string $filePath,
        int $arrowFunctionIndex,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): RetypePlan {
        return $this->planNestedCallableTypeChange(new NestedCallableTypeChangeRequest(
            containerKind: NestedCallableContainerKind::FILE,
            callableKind: NestedCallableKind::ARROW_FUNCTION,
            targetKind: NestedCallableTargetKind::RETURN,
            callableIndex: $arrowFunctionIndex,
            typeNode: $typeNode,
            docType: $docType,
            filePath: $filePath,
            parameterName: null,
            parameterIndex: null,
        ));
    }

    /**
     * Plans and applies an arrow function return type change inside a file.
     *
     * @param string                                                       $filePath           the physical or virtual file path
     * @param int                                                          $arrowFunctionIndex the zero-based arrow-function index
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode           the native PHP type node to write
     * @param string|null                                                  $docType            the PHPDoc type to write in the `@return` tag
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function changeArrowFunctionReturnTypeInFile(
        string $filePath,
        int $arrowFunctionIndex,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): RetypeResult {
        return $this->changeNestedCallableType(new NestedCallableTypeChangeRequest(
            containerKind: NestedCallableContainerKind::FILE,
            callableKind: NestedCallableKind::ARROW_FUNCTION,
            targetKind: NestedCallableTargetKind::RETURN,
            callableIndex: $arrowFunctionIndex,
            typeNode: $typeNode,
            docType: $docType,
            filePath: $filePath,
            parameterName: null,
            parameterIndex: null,
        ));
    }

    /**
     * Plans one nested callable type change.
     *
     * @param NestedCallableTypeChangeRequest $request the nested callable request
     */
    private function planNestedCallableTypeChange(NestedCallableTypeChangeRequest $request): RetypePlan
    {
        return $this->nestedCallableTypeChangePlanner->plan($request, $this->build);
    }

    /**
     * Plans and applies one nested callable type change.
     *
     * @param NestedCallableTypeChangeRequest $request the nested callable request
     */
    private function changeNestedCallableType(NestedCallableTypeChangeRequest $request): RetypeResult
    {
        return $this->retypePlanApplier->apply(
            plan: $this->planNestedCallableTypeChange($request),
            build: $this->build,
        );
    }

    /**
     * Executes one nested callable type-change step.
     *
     * @param RetypeStepContext               $context the current retype step context
     * @param NestedCallableTypeChangeRequest $request the nested callable request
     */
    private function executeStepNestedCallableTypeChange(
        RetypeStepContext $context,
        NestedCallableTypeChangeRequest $request,
    ): RetypeStepResult {
        return $this->executeStep(
            plan: $this->nestedCallableTypeChangePlanner->plan($request, $context->currentBuild),
            context: $context,
        );
    }
}
