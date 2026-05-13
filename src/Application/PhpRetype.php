<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRetype\Application;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphFactory;
use PhpNoobs\PhpRetype\Application\Contract\ClassConstantTypeChangePlannerInterface;
use PhpNoobs\PhpRetype\Application\Contract\EnumBackingTypeChangePlannerInterface;
use PhpNoobs\PhpRetype\Application\Contract\FunctionParameterTypeChangePlannerInterface;
use PhpNoobs\PhpRetype\Application\Contract\FunctionReturnTypeChangePlannerInterface;
use PhpNoobs\PhpRetype\Application\Contract\MethodParameterTypeChangePlannerInterface;
use PhpNoobs\PhpRetype\Application\Contract\MethodReturnTypeChangePlannerInterface;
use PhpNoobs\PhpRetype\Application\Contract\NestedCallableTypeChangePlannerInterface;
use PhpNoobs\PhpRetype\Application\Contract\PropertyTypeChangePlannerInterface;
use PhpNoobs\PhpRetype\Application\Contract\RetypePlanApplierInterface;
use PhpNoobs\PhpRetype\Domain\Retype\Plan\RetypePlan;
use PhpNoobs\PhpRetype\Domain\Retype\Plan\RetypeResult;
use PhpNoobs\PhpRetype\Domain\Retype\Request\ClassConstantTypeChangeRequest;
use PhpNoobs\PhpRetype\Domain\Retype\Request\EnumBackingTypeChangeRequest;
use PhpNoobs\PhpRetype\Domain\Retype\Request\FunctionParameterTypeChangeRequest;
use PhpNoobs\PhpRetype\Domain\Retype\Request\FunctionReturnTypeChangeRequest;
use PhpNoobs\PhpRetype\Domain\Retype\Request\MethodParameterTypeChangeRequest;
use PhpNoobs\PhpRetype\Domain\Retype\Request\MethodReturnTypeChangeRequest;
use PhpNoobs\PhpRetype\Domain\Retype\Request\PropertyTypeChangeRequest;
use PhpNoobs\PhpRetype\Domain\Retype\Step\RetypeStepContext;
use PhpNoobs\PhpRetype\Domain\Retype\Step\RetypeStepResult;
use PhpNoobs\PhpRetype\Infrastructure\MemberGraph\Planner\MemberGraphClassConstantTypeChangePlanner;
use PhpNoobs\PhpRetype\Infrastructure\MemberGraph\Planner\MemberGraphEnumBackingTypeChangePlanner;
use PhpNoobs\PhpRetype\Infrastructure\MemberGraph\Planner\MemberGraphFunctionParameterTypeChangePlanner;
use PhpNoobs\PhpRetype\Infrastructure\MemberGraph\Planner\MemberGraphFunctionReturnTypeChangePlanner;
use PhpNoobs\PhpRetype\Infrastructure\MemberGraph\Planner\MemberGraphMethodParameterTypeChangePlanner;
use PhpNoobs\PhpRetype\Infrastructure\MemberGraph\Planner\MemberGraphMethodReturnTypeChangePlanner;
use PhpNoobs\PhpRetype\Infrastructure\MemberGraph\Planner\MemberGraphNestedCallableTypeChangePlanner;
use PhpNoobs\PhpRetype\Infrastructure\MemberGraph\Planner\MemberGraphPropertyTypeChangePlanner;
use PhpNoobs\PhpRetype\Infrastructure\PhpParser\AstRetypePlanApplier;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\UnionType;

/**
 * Public facade for planning and applying PHP type changes.
 */
final readonly class PhpRetype
{
    use NestedCallablePhpRetypeMethods;

    /**
     * Constructor.
     *
     * @param MemberDependencyGraphBuild                  $build                              the member graph build used by retype operations
     * @param MethodParameterTypeChangePlannerInterface   $methodParameterTypeChangePlanner   the method parameter type change planner
     * @param FunctionParameterTypeChangePlannerInterface $functionParameterTypeChangePlanner the function parameter type change planner
     * @param FunctionReturnTypeChangePlannerInterface    $functionReturnTypeChangePlanner    the function return type change planner
     * @param MethodReturnTypeChangePlannerInterface      $methodReturnTypeChangePlanner      the method return type change planner
     * @param PropertyTypeChangePlannerInterface          $propertyTypeChangePlanner          the property type change planner
     * @param ClassConstantTypeChangePlannerInterface     $classConstantTypeChangePlanner     the class constant type change planner
     * @param EnumBackingTypeChangePlannerInterface       $enumBackingTypeChangePlanner       the enum backing type change planner
     * @param NestedCallableTypeChangePlannerInterface    $nestedCallableTypeChangePlanner    the nested callable type change planner
     * @param RetypePlanApplierInterface                  $retypePlanApplier                  the retype plan applier
     * @param RetypeStepExecutor                          $retypeStepExecutor                 the retype step executor
     */
    private function __construct(
        private MemberDependencyGraphBuild $build,
        private MethodParameterTypeChangePlannerInterface $methodParameterTypeChangePlanner,
        private FunctionParameterTypeChangePlannerInterface $functionParameterTypeChangePlanner,
        private FunctionReturnTypeChangePlannerInterface $functionReturnTypeChangePlanner,
        private MethodReturnTypeChangePlannerInterface $methodReturnTypeChangePlanner,
        private PropertyTypeChangePlannerInterface $propertyTypeChangePlanner,
        private ClassConstantTypeChangePlannerInterface $classConstantTypeChangePlanner,
        private EnumBackingTypeChangePlannerInterface $enumBackingTypeChangePlanner,
        private NestedCallableTypeChangePlannerInterface $nestedCallableTypeChangePlanner,
        private RetypePlanApplierInterface $retypePlanApplier,
        private RetypeStepExecutor $retypeStepExecutor,
    ) {
    }

    /**
     * Creates a retype service from project directories.
     *
     * @param list<string> $directories         the directories to scan
     * @param string       $cacheFilePath       the member graph cache file path
     * @param list<string> $excludedDirectories the directories to exclude from scanning
     * @param bool         $clearCache          whether the member graph cache must be cleared first
     */
    public static function fromDirectory(
        array $directories,
        string $cacheFilePath,
        array $excludedDirectories = [],
        bool $clearCache = false,
    ): self {
        return self::fromBuild(MemberDependencyGraphFactory::fromDirectory(
            directories: $directories,
            cacheFilePath: $cacheFilePath,
            excludedDirectories: $excludedDirectories,
            clearCache: $clearCache,
        ));
    }

    /**
     * Creates a retype service from an existing member graph build.
     *
     * @param MemberDependencyGraphBuild                       $build                              the member graph build
     * @param MethodParameterTypeChangePlannerInterface|null   $methodParameterTypeChangePlanner   the optional method parameter type change planner override
     * @param FunctionParameterTypeChangePlannerInterface|null $functionParameterTypeChangePlanner the optional function parameter type change planner override
     * @param FunctionReturnTypeChangePlannerInterface|null    $functionReturnTypeChangePlanner    the optional function return type change planner override
     * @param MethodReturnTypeChangePlannerInterface|null      $methodReturnTypeChangePlanner      the optional method return type change planner override
     * @param PropertyTypeChangePlannerInterface|null          $propertyTypeChangePlanner          the optional property type change planner override
     * @param ClassConstantTypeChangePlannerInterface|null     $classConstantTypeChangePlanner     the optional class constant type change planner override
     * @param EnumBackingTypeChangePlannerInterface|null       $enumBackingTypeChangePlanner       the optional enum backing type change planner override
     * @param NestedCallableTypeChangePlannerInterface|null    $nestedCallableTypeChangePlanner    the optional nested callable type change planner override
     * @param RetypePlanApplierInterface|null                  $retypePlanApplier                  the optional retype plan applier override
     */
    public static function fromBuild(
        MemberDependencyGraphBuild $build,
        ?MethodParameterTypeChangePlannerInterface $methodParameterTypeChangePlanner = null,
        ?FunctionParameterTypeChangePlannerInterface $functionParameterTypeChangePlanner = null,
        ?FunctionReturnTypeChangePlannerInterface $functionReturnTypeChangePlanner = null,
        ?MethodReturnTypeChangePlannerInterface $methodReturnTypeChangePlanner = null,
        ?PropertyTypeChangePlannerInterface $propertyTypeChangePlanner = null,
        ?ClassConstantTypeChangePlannerInterface $classConstantTypeChangePlanner = null,
        ?EnumBackingTypeChangePlannerInterface $enumBackingTypeChangePlanner = null,
        ?NestedCallableTypeChangePlannerInterface $nestedCallableTypeChangePlanner = null,
        ?RetypePlanApplierInterface $retypePlanApplier = null,
    ): self {
        $retypePlanApplier ??= new AstRetypePlanApplier();

        return new self(
            build: $build,
            methodParameterTypeChangePlanner: $methodParameterTypeChangePlanner ?? new MemberGraphMethodParameterTypeChangePlanner(),
            functionParameterTypeChangePlanner: $functionParameterTypeChangePlanner ?? new MemberGraphFunctionParameterTypeChangePlanner(),
            functionReturnTypeChangePlanner: $functionReturnTypeChangePlanner ?? new MemberGraphFunctionReturnTypeChangePlanner(),
            methodReturnTypeChangePlanner: $methodReturnTypeChangePlanner ?? new MemberGraphMethodReturnTypeChangePlanner(),
            propertyTypeChangePlanner: $propertyTypeChangePlanner ?? new MemberGraphPropertyTypeChangePlanner(),
            classConstantTypeChangePlanner: $classConstantTypeChangePlanner ?? new MemberGraphClassConstantTypeChangePlanner(),
            enumBackingTypeChangePlanner: $enumBackingTypeChangePlanner ?? new MemberGraphEnumBackingTypeChangePlanner(),
            nestedCallableTypeChangePlanner: $nestedCallableTypeChangePlanner ?? new MemberGraphNestedCallableTypeChangePlanner(),
            retypePlanApplier: $retypePlanApplier,
            retypeStepExecutor: new RetypeStepExecutor($retypePlanApplier),
        );
    }

    /**
     * Starts a retype transaction from the current member graph build.
     */
    public function beginTransaction(): PhpRetypeTransaction
    {
        return new PhpRetypeTransaction(
            currentBuild: $this->build,
            methodParameterTypeChangePlanner: $this->methodParameterTypeChangePlanner,
            functionParameterTypeChangePlanner: $this->functionParameterTypeChangePlanner,
            functionReturnTypeChangePlanner: $this->functionReturnTypeChangePlanner,
            methodReturnTypeChangePlanner: $this->methodReturnTypeChangePlanner,
            propertyTypeChangePlanner: $this->propertyTypeChangePlanner,
            classConstantTypeChangePlanner: $this->classConstantTypeChangePlanner,
            enumBackingTypeChangePlanner: $this->enumBackingTypeChangePlanner,
            nestedCallableTypeChangePlanner: $this->nestedCallableTypeChangePlanner,
            retypePlanApplier: $this->retypePlanApplier,
        );
    }

    /**
     * Executes one preplanned orchestrable retype step.
     *
     * @param RetypePlan        $plan    the retype plan to execute
     * @param RetypeStepContext $context the current retype step context
     */
    public function executeStep(RetypePlan $plan, RetypeStepContext $context): RetypeStepResult
    {
        return $this->retypeStepExecutor->execute($plan, $context);
    }

    /**
     * Executes one orchestrable method parameter type-change step.
     *
     * @param RetypeStepContext                                            $context        the current retype step context
     * @param string                                                       $className      the method owner FQCN
     * @param string                                                       $methodName     the method name
     * @param string                                                       $parameterName  the parameter name without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode       the native PHP type node to write
     * @param string|null                                                  $docType        the PHPDoc type to write in the `@param` tag
     * @param int|null                                                     $parameterIndex the optional zero-based declaration index
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function executeStepMethodParameterTypeChange(
        RetypeStepContext $context,
        string $className,
        string $methodName,
        string $parameterName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
        ?int $parameterIndex = null,
    ): RetypeStepResult {
        return $this->executeStep($this->methodParameterTypeChangePlanner->plan(
            request: new MethodParameterTypeChangeRequest(
                className: $className,
                methodName: $methodName,
                parameterName: $parameterName,
                typeNode: $typeNode,
                docType: $docType,
                parameterIndex: $parameterIndex,
            ),
            build: $context->currentBuild,
        ), $context);
    }

    /**
     * Executes one orchestrable function parameter type-change step.
     *
     * @param RetypeStepContext                                            $context        the current retype step context
     * @param string                                                       $functionName   the fully-qualified function name
     * @param string                                                       $parameterName  the parameter name without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode       the native PHP type node to write
     * @param string|null                                                  $docType        the PHPDoc type to write in the `@param` tag
     * @param int|null                                                     $parameterIndex the optional zero-based declaration index
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function executeStepFunctionParameterTypeChange(
        RetypeStepContext $context,
        string $functionName,
        string $parameterName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
        ?int $parameterIndex = null,
    ): RetypeStepResult {
        return $this->executeStep($this->functionParameterTypeChangePlanner->plan(
            request: new FunctionParameterTypeChangeRequest(
                functionName: $functionName,
                parameterName: $parameterName,
                typeNode: $typeNode,
                docType: $docType,
                parameterIndex: $parameterIndex,
            ),
            build: $context->currentBuild,
        ), $context);
    }

    /**
     * Executes one orchestrable function return type-change step.
     *
     * @param RetypeStepContext                                            $context      the current retype step context
     * @param string                                                       $functionName the fully-qualified function name
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode     the native PHP type node to write
     * @param string|null                                                  $docType      the PHPDoc type to write in the `@return` tag
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function executeStepFunctionReturnTypeChange(
        RetypeStepContext $context,
        string $functionName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): RetypeStepResult {
        return $this->executeStep($this->functionReturnTypeChangePlanner->plan(
            request: new FunctionReturnTypeChangeRequest(
                functionName: $functionName,
                typeNode: $typeNode,
                docType: $docType,
            ),
            build: $context->currentBuild,
        ), $context);
    }

    /**
     * Executes one orchestrable method return type-change step.
     *
     * @param RetypeStepContext                                            $context    the current retype step context
     * @param string                                                       $className  the method owner FQCN
     * @param string                                                       $methodName the method name
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode   the native PHP type node to write
     * @param string|null                                                  $docType    the PHPDoc type to write in the `@return` tag
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function executeStepMethodReturnTypeChange(
        RetypeStepContext $context,
        string $className,
        string $methodName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): RetypeStepResult {
        return $this->executeStep($this->methodReturnTypeChangePlanner->plan(
            request: new MethodReturnTypeChangeRequest(
                className: $className,
                methodName: $methodName,
                typeNode: $typeNode,
                docType: $docType,
            ),
            build: $context->currentBuild,
        ), $context);
    }

    /**
     * Executes one orchestrable property type-change step.
     *
     * @param RetypeStepContext                                            $context       the current retype step context
     * @param string                                                       $className     the property owner FQCN
     * @param string|list<string>                                          $propertyNames the property name or property names without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode      the native PHP type node to write
     * @param string|null                                                  $docType       the PHPDoc type to write in the `@var` tag
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function executeStepPropertyTypeChange(
        RetypeStepContext $context,
        string $className,
        string|array $propertyNames,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): RetypeStepResult {
        return $this->executeStep($this->propertyTypeChangePlanner->plan(
            request: new PropertyTypeChangeRequest(
                className: $className,
                propertyNames: $propertyNames,
                typeNode: $typeNode,
                docType: $docType,
            ),
            build: $context->currentBuild,
        ), $context);
    }

    /**
     * Executes one orchestrable class constant type-change step.
     *
     * @param RetypeStepContext                                            $context      the current retype step context
     * @param string                                                       $className    the class-like owner FQCN
     * @param string                                                       $constantName the class constant name
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode     the native PHP type node to write
     * @param string|null                                                  $docType      the PHPDoc type to write in the `@var` tag
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function executeStepClassConstantTypeChange(
        RetypeStepContext $context,
        string $className,
        string $constantName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): RetypeStepResult {
        return $this->executeStep($this->classConstantTypeChangePlanner->plan(
            request: new ClassConstantTypeChangeRequest(
                className: $className,
                constantName: $constantName,
                typeNode: $typeNode,
                docType: $docType,
            ),
            build: $context->currentBuild,
        ), $context);
    }

    /**
     * Executes one orchestrable enum backing type-change step.
     *
     * @param RetypeStepContext $context  the current retype step context
     * @param string            $enumName the enum FQCN
     * @param Identifier        $typeNode the native PHP backing type node to write
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function executeStepEnumBackingTypeChange(
        RetypeStepContext $context,
        string $enumName,
        Identifier $typeNode,
    ): RetypeStepResult {
        return $this->executeStep($this->enumBackingTypeChangePlanner->plan(
            request: new EnumBackingTypeChangeRequest(
                enumName: $enumName,
                typeNode: $typeNode,
            ),
            build: $context->currentBuild,
        ), $context);
    }

    /**
     * Plans a method parameter type change.
     *
     * @param string                                                       $className      the method owner FQCN
     * @param string                                                       $methodName     the method name
     * @param string                                                       $parameterName  the parameter name without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode       the native PHP type node to write
     * @param string|null                                                  $docType        the PHPDoc type to write in the `@param` tag
     * @param int|null                                                     $parameterIndex the optional zero-based declaration index
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function planMethodParameterTypeChange(
        string $className,
        string $methodName,
        string $parameterName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
        ?int $parameterIndex = null,
    ): RetypePlan {
        return $this->methodParameterTypeChangePlanner->plan(
            request: new MethodParameterTypeChangeRequest(
                className: $className,
                methodName: $methodName,
                parameterName: $parameterName,
                typeNode: $typeNode,
                docType: $docType,
                parameterIndex: $parameterIndex,
            ),
            build: $this->build,
        );
    }

    /**
     * Plans and applies a method parameter type change to virtual file AST nodes.
     *
     * @param string                                                       $className      the method owner FQCN
     * @param string                                                       $methodName     the method name
     * @param string                                                       $parameterName  the parameter name without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode       the native PHP type node to write
     * @param string|null                                                  $docType        the PHPDoc type to write in the `@param` tag
     * @param int|null                                                     $parameterIndex the optional zero-based declaration index
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function changeMethodParameterType(
        string $className,
        string $methodName,
        string $parameterName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
        ?int $parameterIndex = null,
    ): RetypeResult {
        return $this->retypePlanApplier->apply(
            plan: $this->planMethodParameterTypeChange(
                className: $className,
                methodName: $methodName,
                parameterName: $parameterName,
                typeNode: $typeNode,
                docType: $docType,
                parameterIndex: $parameterIndex,
            ),
            build: $this->build,
        );
    }

    /**
     * Plans a function parameter type change.
     *
     * @param string                                                       $functionName   the fully-qualified function name
     * @param string                                                       $parameterName  the parameter name without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode       the native PHP type node to write
     * @param string|null                                                  $docType        the PHPDoc type to write in the `@param` tag
     * @param int|null                                                     $parameterIndex the optional zero-based declaration index
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function planFunctionParameterTypeChange(
        string $functionName,
        string $parameterName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
        ?int $parameterIndex = null,
    ): RetypePlan {
        return $this->functionParameterTypeChangePlanner->plan(
            request: new FunctionParameterTypeChangeRequest(
                functionName: $functionName,
                parameterName: $parameterName,
                typeNode: $typeNode,
                docType: $docType,
                parameterIndex: $parameterIndex,
            ),
            build: $this->build,
        );
    }

    /**
     * Plans and applies a function parameter type change to virtual file AST nodes.
     *
     * @param string                                                       $functionName   the fully-qualified function name
     * @param string                                                       $parameterName  the parameter name without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode       the native PHP type node to write
     * @param string|null                                                  $docType        the PHPDoc type to write in the `@param` tag
     * @param int|null                                                     $parameterIndex the optional zero-based declaration index
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function changeFunctionParameterType(
        string $functionName,
        string $parameterName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
        ?int $parameterIndex = null,
    ): RetypeResult {
        return $this->retypePlanApplier->apply(
            plan: $this->planFunctionParameterTypeChange(
                functionName: $functionName,
                parameterName: $parameterName,
                typeNode: $typeNode,
                docType: $docType,
                parameterIndex: $parameterIndex,
            ),
            build: $this->build,
        );
    }

    /**
     * Plans a function return type change.
     *
     * @param string                                                       $functionName the fully-qualified function name
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode     the native PHP type node to write
     * @param string|null                                                  $docType      the PHPDoc type to write in the `@return` tag
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function planFunctionReturnTypeChange(
        string $functionName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): RetypePlan {
        return $this->functionReturnTypeChangePlanner->plan(
            request: new FunctionReturnTypeChangeRequest(
                functionName: $functionName,
                typeNode: $typeNode,
                docType: $docType,
            ),
            build: $this->build,
        );
    }

    /**
     * Plans and applies a function return type change to virtual file AST nodes.
     *
     * @param string                                                       $functionName the fully-qualified function name
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode     the native PHP type node to write
     * @param string|null                                                  $docType      the PHPDoc type to write in the `@return` tag
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function changeFunctionReturnType(
        string $functionName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): RetypeResult {
        return $this->retypePlanApplier->apply(
            plan: $this->planFunctionReturnTypeChange(
                functionName: $functionName,
                typeNode: $typeNode,
                docType: $docType,
            ),
            build: $this->build,
        );
    }

    /**
     * Plans a method return type change.
     *
     * @param string                                                       $className  the method owner FQCN
     * @param string                                                       $methodName the method name
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode   the native PHP type node to write
     * @param string|null                                                  $docType    the PHPDoc type to write in the `@return` tag
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function planMethodReturnTypeChange(
        string $className,
        string $methodName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): RetypePlan {
        return $this->methodReturnTypeChangePlanner->plan(
            request: new MethodReturnTypeChangeRequest(
                className: $className,
                methodName: $methodName,
                typeNode: $typeNode,
                docType: $docType,
            ),
            build: $this->build,
        );
    }

    /**
     * Plans and applies a method return type change to virtual file AST nodes.
     *
     * @param string                                                       $className  the method owner FQCN
     * @param string                                                       $methodName the method name
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode   the native PHP type node to write
     * @param string|null                                                  $docType    the PHPDoc type to write in the `@return` tag
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function changeMethodReturnType(
        string $className,
        string $methodName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): RetypeResult {
        return $this->retypePlanApplier->apply(
            plan: $this->planMethodReturnTypeChange(
                className: $className,
                methodName: $methodName,
                typeNode: $typeNode,
                docType: $docType,
            ),
            build: $this->build,
        );
    }

    /**
     * Plans a property type change.
     *
     * @param string                                                       $className     the property owner FQCN
     * @param string|list<string>                                          $propertyNames the property name or property names without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode      the native PHP type node to write
     * @param string|null                                                  $docType       the PHPDoc type to write in the `@var` tag
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function planPropertyTypeChange(
        string $className,
        string|array $propertyNames,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): RetypePlan {
        return $this->propertyTypeChangePlanner->plan(
            request: new PropertyTypeChangeRequest(
                className: $className,
                propertyNames: $propertyNames,
                typeNode: $typeNode,
                docType: $docType,
            ),
            build: $this->build,
        );
    }

    /**
     * Plans and applies a property type change to virtual file AST nodes.
     *
     * @param string                                                       $className     the property owner FQCN
     * @param string|list<string>                                          $propertyNames the property name or property names without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode      the native PHP type node to write
     * @param string|null                                                  $docType       the PHPDoc type to write in the `@var` tag
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function changePropertyType(
        string $className,
        string|array $propertyNames,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): RetypeResult {
        return $this->retypePlanApplier->apply(
            plan: $this->planPropertyTypeChange(
                className: $className,
                propertyNames: $propertyNames,
                typeNode: $typeNode,
                docType: $docType,
            ),
            build: $this->build,
        );
    }

    /**
     * Plans a class constant type change.
     *
     * @param string                                                       $className    the class-like owner FQCN
     * @param string                                                       $constantName the class constant name
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode     the native PHP type node to write
     * @param string|null                                                  $docType      the PHPDoc type to write in the `@var` tag
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function planClassConstantTypeChange(
        string $className,
        string $constantName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): RetypePlan {
        return $this->classConstantTypeChangePlanner->plan(
            request: new ClassConstantTypeChangeRequest(
                className: $className,
                constantName: $constantName,
                typeNode: $typeNode,
                docType: $docType,
            ),
            build: $this->build,
        );
    }

    /**
     * Plans and applies a class constant type change to virtual file AST nodes.
     *
     * @param string                                                       $className    the class-like owner FQCN
     * @param string                                                       $constantName the class constant name
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode     the native PHP type node to write
     * @param string|null                                                  $docType      the PHPDoc type to write in the `@var` tag
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function changeClassConstantType(
        string $className,
        string $constantName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): RetypeResult {
        return $this->retypePlanApplier->apply(
            plan: $this->planClassConstantTypeChange(
                className: $className,
                constantName: $constantName,
                typeNode: $typeNode,
                docType: $docType,
            ),
            build: $this->build,
        );
    }

    /**
     * Plans an enum backing type change.
     *
     * @param string     $enumName the enum FQCN
     * @param Identifier $typeNode the native PHP backing type node to write
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function planEnumBackingTypeChange(string $enumName, Identifier $typeNode): RetypePlan
    {
        return $this->enumBackingTypeChangePlanner->plan(
            request: new EnumBackingTypeChangeRequest(
                enumName: $enumName,
                typeNode: $typeNode,
            ),
            build: $this->build,
        );
    }

    /**
     * Plans and applies an enum backing type change to virtual file AST nodes.
     *
     * @param string     $enumName the enum FQCN
     * @param Identifier $typeNode the native PHP backing type node to write
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function changeEnumBackingType(string $enumName, Identifier $typeNode): RetypeResult
    {
        return $this->retypePlanApplier->apply(
            plan: $this->planEnumBackingTypeChange(
                enumName: $enumName,
                typeNode: $typeNode,
            ),
            build: $this->build,
        );
    }
}
