<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRetype\Application;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphFactory;
use PhpNoobs\PhpRetype\Application\Contract\FunctionParameterTypeChangePlannerInterface;
use PhpNoobs\PhpRetype\Application\Contract\FunctionReturnTypeChangePlannerInterface;
use PhpNoobs\PhpRetype\Application\Contract\MethodParameterTypeChangePlannerInterface;
use PhpNoobs\PhpRetype\Application\Contract\RetypePlanApplierInterface;
use PhpNoobs\PhpRetype\Domain\Retype\Plan\RetypePlan;
use PhpNoobs\PhpRetype\Domain\Retype\Plan\RetypeResult;
use PhpNoobs\PhpRetype\Domain\Retype\Request\FunctionParameterTypeChangeRequest;
use PhpNoobs\PhpRetype\Domain\Retype\Request\FunctionReturnTypeChangeRequest;
use PhpNoobs\PhpRetype\Domain\Retype\Request\MethodParameterTypeChangeRequest;
use PhpNoobs\PhpRetype\Infrastructure\MemberGraph\Planner\MemberGraphFunctionParameterTypeChangePlanner;
use PhpNoobs\PhpRetype\Infrastructure\MemberGraph\Planner\MemberGraphFunctionReturnTypeChangePlanner;
use PhpNoobs\PhpRetype\Infrastructure\MemberGraph\Planner\MemberGraphMethodParameterTypeChangePlanner;
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
    /**
     * Constructor.
     *
     * @param MemberDependencyGraphBuild                  $build                              the member graph build used by retype operations
     * @param MethodParameterTypeChangePlannerInterface   $methodParameterTypeChangePlanner   the method parameter type change planner
     * @param FunctionParameterTypeChangePlannerInterface $functionParameterTypeChangePlanner the function parameter type change planner
     * @param FunctionReturnTypeChangePlannerInterface    $functionReturnTypeChangePlanner    the function return type change planner
     * @param RetypePlanApplierInterface                  $retypePlanApplier                  the retype plan applier
     */
    private function __construct(
        private MemberDependencyGraphBuild $build,
        private MethodParameterTypeChangePlannerInterface $methodParameterTypeChangePlanner,
        private FunctionParameterTypeChangePlannerInterface $functionParameterTypeChangePlanner,
        private FunctionReturnTypeChangePlannerInterface $functionReturnTypeChangePlanner,
        private RetypePlanApplierInterface $retypePlanApplier,
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
     * @param RetypePlanApplierInterface|null                  $retypePlanApplier                  the optional retype plan applier override
     */
    public static function fromBuild(
        MemberDependencyGraphBuild $build,
        ?MethodParameterTypeChangePlannerInterface $methodParameterTypeChangePlanner = null,
        ?FunctionParameterTypeChangePlannerInterface $functionParameterTypeChangePlanner = null,
        ?FunctionReturnTypeChangePlannerInterface $functionReturnTypeChangePlanner = null,
        ?RetypePlanApplierInterface $retypePlanApplier = null,
    ): self {
        return new self(
            build: $build,
            methodParameterTypeChangePlanner: $methodParameterTypeChangePlanner ?? new MemberGraphMethodParameterTypeChangePlanner(),
            functionParameterTypeChangePlanner: $functionParameterTypeChangePlanner ?? new MemberGraphFunctionParameterTypeChangePlanner(),
            functionReturnTypeChangePlanner: $functionReturnTypeChangePlanner ?? new MemberGraphFunctionReturnTypeChangePlanner(),
            retypePlanApplier: $retypePlanApplier ?? new AstRetypePlanApplier(),
        );
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
}
