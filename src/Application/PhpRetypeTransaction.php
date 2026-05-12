<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRetype\Application;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphFactory;
use PhpNoobs\PhpRetype\Application\Contract\FunctionParameterTypeChangePlannerInterface;
use PhpNoobs\PhpRetype\Application\Contract\FunctionReturnTypeChangePlannerInterface;
use PhpNoobs\PhpRetype\Application\Contract\MethodParameterTypeChangePlannerInterface;
use PhpNoobs\PhpRetype\Application\Contract\MethodReturnTypeChangePlannerInterface;
use PhpNoobs\PhpRetype\Application\Contract\PropertyTypeChangePlannerInterface;
use PhpNoobs\PhpRetype\Application\Contract\RetypePlanApplierInterface;
use PhpNoobs\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnostic;
use PhpNoobs\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnosticCollection;
use PhpNoobs\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnosticSeverity;
use PhpNoobs\PhpRetype\Domain\Retype\Operation\RetypeOperation;
use PhpNoobs\PhpRetype\Domain\Retype\Plan\RetypePlan;
use PhpNoobs\PhpRetype\Domain\Retype\Plan\RetypeResult;
use PhpNoobs\PhpRetype\Domain\Retype\Step\RetypeStepContext;
use PhpNoobs\PhpRetype\Domain\Retype\Transaction\RetypeTransactionResult;
use PhpNoobs\PhpRetype\Domain\Retype\Transaction\RetypeTransactionStatus;
use PhpNoobs\PhpRetype\Infrastructure\PhpParser\Transaction\VirtualPhpSourceFileSnapshot;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\UnionType;

/**
 * Applies multiple retype actions against a refreshed in-memory member graph build.
 */
final class PhpRetypeTransaction
{
    /**
     * @var list<RetypeResult>
     */
    private array $actionResults = [];

    /**
     * @var array<string, VirtualPhpSourceFileSnapshot>
     */
    private array $snapshots = [];

    private RetypeTransactionStatus $status = RetypeTransactionStatus::ACTIVE;
    private RetypeDiagnosticCollection $diagnostics;
    private RetypeStepExecutor $stepExecutor;

    /**
     * Constructor.
     *
     * @param MemberDependencyGraphBuild                  $currentBuild                       the current transaction build
     * @param MethodParameterTypeChangePlannerInterface   $methodParameterTypeChangePlanner   the method parameter type-change planner
     * @param FunctionParameterTypeChangePlannerInterface $functionParameterTypeChangePlanner the function parameter type-change planner
     * @param FunctionReturnTypeChangePlannerInterface    $functionReturnTypeChangePlanner    the function return type-change planner
     * @param MethodReturnTypeChangePlannerInterface      $methodReturnTypeChangePlanner      the method return type-change planner
     * @param PropertyTypeChangePlannerInterface          $propertyTypeChangePlanner          the property type-change planner
     * @param RetypePlanApplierInterface                  $retypePlanApplier                  the retype plan applier
     */
    public function __construct(
        private MemberDependencyGraphBuild $currentBuild,
        private readonly MethodParameterTypeChangePlannerInterface $methodParameterTypeChangePlanner,
        private readonly FunctionParameterTypeChangePlannerInterface $functionParameterTypeChangePlanner,
        private readonly FunctionReturnTypeChangePlannerInterface $functionReturnTypeChangePlanner,
        private readonly MethodReturnTypeChangePlannerInterface $methodReturnTypeChangePlanner,
        private readonly PropertyTypeChangePlannerInterface $propertyTypeChangePlanner,
        private readonly RetypePlanApplierInterface $retypePlanApplier,
    ) {
        $this->diagnostics = RetypeDiagnosticCollection::empty();
        $this->stepExecutor = new RetypeStepExecutor($this->retypePlanApplier);
    }

    /**
     * Plans and applies a method parameter type change within the transaction.
     *
     * @param string                                                       $className      the method owner FQCN
     * @param string                                                       $methodName     the method name
     * @param string                                                       $parameterName  the parameter name without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode       the native PHP type node to write
     * @param string|null                                                  $docType        the PHPDoc type to write in the `@param` tag
     * @param int|null                                                     $parameterIndex the optional zero-based declaration index
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     * @throws \LogicException           when the transaction is no longer active
     */
    public function changeMethodParameterType(
        string $className,
        string $methodName,
        string $parameterName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
        ?int $parameterIndex = null,
    ): RetypeResult {
        return $this->execute($this->retyper()->planMethodParameterTypeChange(
            className: $className,
            methodName: $methodName,
            parameterName: $parameterName,
            typeNode: $typeNode,
            docType: $docType,
            parameterIndex: $parameterIndex,
        ));
    }

    /**
     * Plans and applies a function parameter type change within the transaction.
     *
     * @param string                                                       $functionName   the fully-qualified function name
     * @param string                                                       $parameterName  the parameter name without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode       the native PHP type node to write
     * @param string|null                                                  $docType        the PHPDoc type to write in the `@param` tag
     * @param int|null                                                     $parameterIndex the optional zero-based declaration index
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     * @throws \LogicException           when the transaction is no longer active
     */
    public function changeFunctionParameterType(
        string $functionName,
        string $parameterName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
        ?int $parameterIndex = null,
    ): RetypeResult {
        return $this->execute($this->retyper()->planFunctionParameterTypeChange(
            functionName: $functionName,
            parameterName: $parameterName,
            typeNode: $typeNode,
            docType: $docType,
            parameterIndex: $parameterIndex,
        ));
    }

    /**
     * Plans and applies a function return type change within the transaction.
     *
     * @param string                                                       $functionName the fully-qualified function name
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode     the native PHP type node to write
     * @param string|null                                                  $docType      the PHPDoc type to write in the `@return` tag
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     * @throws \LogicException           when the transaction is no longer active
     */
    public function changeFunctionReturnType(
        string $functionName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): RetypeResult {
        return $this->execute($this->retyper()->planFunctionReturnTypeChange(
            functionName: $functionName,
            typeNode: $typeNode,
            docType: $docType,
        ));
    }

    /**
     * Plans and applies a method return type change within the transaction.
     *
     * @param string                                                       $className  the method owner FQCN
     * @param string                                                       $methodName the method name
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode   the native PHP type node to write
     * @param string|null                                                  $docType    the PHPDoc type to write in the `@return` tag
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     * @throws \LogicException           when the transaction is no longer active
     */
    public function changeMethodReturnType(
        string $className,
        string $methodName,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): RetypeResult {
        return $this->execute($this->retyper()->planMethodReturnTypeChange(
            className: $className,
            methodName: $methodName,
            typeNode: $typeNode,
            docType: $docType,
        ));
    }

    /**
     * Plans and applies a property type change within the transaction.
     *
     * @param string                                                       $className     the property owner FQCN
     * @param string|list<string>                                          $propertyNames the property name or property names without "$"
     * @param Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode      the native PHP type node to write
     * @param string|null                                                  $docType       the PHPDoc type to write in the `@var` tag
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     * @throws \LogicException           when the transaction is no longer active
     */
    public function changePropertyType(
        string $className,
        string|array $propertyNames,
        Identifier|Name|NullableType|UnionType|IntersectionType|null $typeNode,
        ?string $docType = null,
    ): RetypeResult {
        return $this->execute($this->retyper()->planPropertyTypeChange(
            className: $className,
            propertyNames: $propertyNames,
            typeNode: $typeNode,
            docType: $docType,
        ));
    }

    /**
     * Commits the transaction and returns its aggregate result.
     */
    public function commit(): RetypeTransactionResult
    {
        if (RetypeTransactionStatus::ACTIVE === $this->status) {
            $this->status = RetypeTransactionStatus::COMMITTED;
        }

        return $this->result($this->status);
    }

    /**
     * Commits the transaction and writes every updated source file.
     *
     * @throws \RuntimeException when source writing fails
     */
    public function commitAndSave(): RetypeTransactionResult
    {
        $result = $this->commit();

        if (RetypeTransactionStatus::COMMITTED === $result->status) {
            $result->finalBuild->sourceRegistry()->save();
        }

        return $result;
    }

    /**
     * Commits the transaction and writes one updated physical source file.
     *
     * @param string $filePath the physical source file path to save
     *
     * @throws \RuntimeException when the source file is unknown or source writing fails
     */
    public function commitAndSaveSourceFile(string $filePath): RetypeTransactionResult
    {
        $result = $this->commit();

        if (RetypeTransactionStatus::COMMITTED === $result->status) {
            $result->finalBuild->sourceRegistry()->saveSourceFile($filePath);
        }

        return $result;
    }

    /**
     * Rolls back all virtual files touched by successful transaction actions.
     */
    public function rollback(): RetypeTransactionResult
    {
        foreach ($this->currentBuild->virtualFiles as $virtualFile) {
            $snapshot = $this->snapshots[$virtualFile->virtualFilePath] ?? null;

            if (null === $snapshot) {
                continue;
            }

            $snapshot->restore($virtualFile);
        }

        $this->currentBuild = MemberDependencyGraphFactory::fromVirtualFiles($this->currentBuild->virtualFiles);
        $this->status = RetypeTransactionStatus::ROLLED_BACK;

        return $this->result($this->status);
    }

    /**
     * Returns the current transaction status.
     */
    public function status(): RetypeTransactionStatus
    {
        return $this->status;
    }

    /**
     * Executes one planned retype action.
     *
     * @param RetypePlan $plan the plan to execute
     *
     * @throws \LogicException when the transaction is no longer active
     */
    private function execute(RetypePlan $plan): RetypeResult
    {
        $this->guardActive();

        if (!$plan->diagnostics->hasErrors()) {
            $this->snapshotPlanVirtualFiles($plan);
        }

        $stepResult = $this->stepExecutor->execute($plan, RetypeStepContext::fromBuild($this->currentBuild));
        $this->currentBuild = $stepResult->context->currentBuild;
        $this->mergeDiagnostics($stepResult->diagnostics);
        $result = $stepResult->retypeResult;
        $this->actionResults[] = $result;

        if ($stepResult->diagnostics->hasErrors()) {
            $this->status = RetypeTransactionStatus::FAILED;
        }

        return $result;
    }

    /**
     * Stores snapshots for virtual files touched by one plan.
     *
     * @param RetypePlan $plan the plan to inspect
     */
    private function snapshotPlanVirtualFiles(RetypePlan $plan): void
    {
        foreach ($plan->operations as $operation) {
            $this->snapshotVirtualFile($operation);
        }
    }

    /**
     * Stores a snapshot for one operation virtual file.
     *
     * @param RetypeOperation $operation the operation to inspect
     */
    private function snapshotVirtualFile(RetypeOperation $operation): void
    {
        $virtualFile = $operation->file;

        if (isset($this->snapshots[$virtualFile->virtualFilePath])) {
            return;
        }

        $this->snapshots[$virtualFile->virtualFilePath] = VirtualPhpSourceFileSnapshot::fromVirtualFile($virtualFile);
    }

    /**
     * Adds diagnostics to the aggregate collection.
     *
     * @param RetypeDiagnosticCollection $diagnostics the diagnostics to merge
     */
    private function mergeDiagnostics(RetypeDiagnosticCollection $diagnostics): void
    {
        foreach ($diagnostics as $diagnostic) {
            $this->diagnostics->add($diagnostic);
        }
    }

    /**
     * Creates a transaction result for the current state.
     *
     * @param RetypeTransactionStatus $status the status to expose
     */
    private function result(RetypeTransactionStatus $status): RetypeTransactionResult
    {
        return new RetypeTransactionResult(
            status: $status,
            actionResults: $this->actionResults,
            finalBuild: $this->currentBuild,
            virtualFiles: $this->currentBuild->virtualFiles,
            diagnostics: $this->diagnostics,
        );
    }

    /**
     * Creates a facade bound to the current transaction build and shared services.
     */
    private function retyper(): PhpRetype
    {
        return PhpRetype::fromBuild(
            build: $this->currentBuild,
            methodParameterTypeChangePlanner: $this->methodParameterTypeChangePlanner,
            functionParameterTypeChangePlanner: $this->functionParameterTypeChangePlanner,
            functionReturnTypeChangePlanner: $this->functionReturnTypeChangePlanner,
            methodReturnTypeChangePlanner: $this->methodReturnTypeChangePlanner,
            propertyTypeChangePlanner: $this->propertyTypeChangePlanner,
            retypePlanApplier: $this->retypePlanApplier,
        );
    }

    /**
     * Ensures the transaction is active before accepting another retype action.
     *
     * @throws \LogicException when the transaction is no longer active
     */
    private function guardActive(): void
    {
        if (RetypeTransactionStatus::ACTIVE === $this->status) {
            return;
        }

        $this->diagnostics->add(new RetypeDiagnostic(
            severity: RetypeDiagnosticSeverity::ERROR,
            message: 'Cannot execute a retype action on a non-active transaction.',
        ));

        throw new \LogicException('Cannot execute a retype action on a non-active transaction.');
    }
}
