<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRetype\Infrastructure\PhpParser;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use PhpNoobs\PhpRetype\Application\Contract\RetypePlanApplierInterface;
use PhpNoobs\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnostic;
use PhpNoobs\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnosticCollection;
use PhpNoobs\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnosticSeverity;
use PhpNoobs\PhpRetype\Domain\Retype\Operation\RetypeOperation;
use PhpNoobs\PhpRetype\Domain\Retype\Plan\RetypePlan;
use PhpNoobs\PhpRetype\Domain\Retype\Plan\RetypeResult;
use PhpNoobs\PhpRetype\Infrastructure\PhpParser\Application\RetypeApplicationContext;
use PhpNoobs\PhpRetype\Infrastructure\PhpParser\Application\RetypeMetadataApplierInterface;
use PhpNoobs\PhpRetype\Infrastructure\PhpParser\Application\RetypeNodeApplierInterface;
use PhpNoobs\PhpRetype\Infrastructure\PhpParser\Callable_\CallableReturnTypeNodeApplier;
use PhpNoobs\PhpRetype\Infrastructure\PhpParser\ClassConstant\ClassConstantTypeNodeApplier;
use PhpNoobs\PhpRetype\Infrastructure\PhpParser\Docblock\ParameterDocblockTypeApplier;
use PhpNoobs\PhpRetype\Infrastructure\PhpParser\Docblock\ReturnDocblockTypeApplier;
use PhpNoobs\PhpRetype\Infrastructure\PhpParser\Docblock\VarDocblockTypeApplier;
use PhpNoobs\PhpRetype\Infrastructure\PhpParser\Enum_\EnumBackingTypeNodeApplier;
use PhpNoobs\PhpRetype\Infrastructure\PhpParser\Function_\FunctionReturnTypeNodeApplier;
use PhpNoobs\PhpRetype\Infrastructure\PhpParser\Method\MethodReturnTypeNodeApplier;
use PhpNoobs\PhpRetype\Infrastructure\PhpParser\Parameter\ParameterTypeNodeApplier;
use PhpNoobs\PhpRetype\Infrastructure\PhpParser\Property\PropertyTypeNodeApplier;
use PhpNoobs\PhpSource\VirtualPhpSourceFile;

/**
 * Applies retype plans to PHPParser AST nodes stored in virtual files.
 */
final readonly class AstRetypePlanApplier implements RetypePlanApplierInterface
{
    /**
     * @var list<RetypeNodeApplierInterface>
     */
    private array $nodeAppliers;

    /**
     * @var list<RetypeMetadataApplierInterface>
     */
    private array $metadataAppliers;

    /**
     * Constructor.
     *
     * @param list<RetypeNodeApplierInterface>|null     $nodeAppliers     the optional node appliers
     * @param list<RetypeMetadataApplierInterface>|null $metadataAppliers the optional metadata appliers
     */
    public function __construct(
        ?array $nodeAppliers = null,
        ?array $metadataAppliers = null,
    ) {
        $this->nodeAppliers = $nodeAppliers ?? [
            new ParameterTypeNodeApplier(),
            new FunctionReturnTypeNodeApplier(),
            new MethodReturnTypeNodeApplier(),
            new PropertyTypeNodeApplier(),
            new ClassConstantTypeNodeApplier(),
            new EnumBackingTypeNodeApplier(),
            new CallableReturnTypeNodeApplier(),
        ];
        $this->metadataAppliers = $metadataAppliers ?? [
            new ParameterDocblockTypeApplier(),
            new ReturnDocblockTypeApplier(),
            new VarDocblockTypeApplier(),
        ];
    }

    /**
     * Applies a retype plan.
     *
     * @param RetypePlan                 $plan  the retype plan to apply
     * @param MemberDependencyGraphBuild $build the member graph build containing virtual files
     */
    public function apply(RetypePlan $plan, MemberDependencyGraphBuild $build): RetypeResult
    {
        $diagnostics = RetypeDiagnosticCollection::empty();

        if ($plan->diagnostics->hasErrors()) {
            return new RetypeResult(
                plan: $plan,
                virtualFiles: $build->virtualFiles,
                diagnostics: $diagnostics,
            );
        }

        $context = new RetypeApplicationContext($diagnostics);
        /** @var array<string, VirtualPhpSourceFile> $updatedVirtualFiles */
        $updatedVirtualFiles = [];

        foreach ($plan->operations as $operation) {
            if (!$this->applyOperation($operation, $context)) {
                continue;
            }

            $updatedVirtualFiles[$operation->file->virtualFilePath] = $operation->file;
        }

        foreach ($updatedVirtualFiles as $virtualFile) {
            $virtualFile->update($virtualFile->nodes);
        }

        return new RetypeResult(
            plan: $plan,
            virtualFiles: $build->virtualFiles,
            diagnostics: $diagnostics,
        );
    }

    /**
     * Applies one retype operation.
     *
     * @param RetypeOperation          $operation the retype operation
     * @param RetypeApplicationContext $context   the retype application context
     */
    private function applyOperation(RetypeOperation $operation, RetypeApplicationContext $context): bool
    {
        foreach ($this->nodeAppliers as $nodeApplier) {
            if (!$nodeApplier->supports($operation)) {
                continue;
            }

            if (!$nodeApplier->apply($operation, $context)) {
                return false;
            }

            $this->applyMetadata($operation, $context);

            return true;
        }

        $context->diagnostics->add(new RetypeDiagnostic(
            severity: RetypeDiagnosticSeverity::WARNING,
            message: 'Unsupported retype target kind.',
        ));

        return false;
    }

    /**
     * Applies metadata mutations associated with one retype operation.
     *
     * @param RetypeOperation          $operation the retype operation
     * @param RetypeApplicationContext $context   the retype application context
     */
    private function applyMetadata(RetypeOperation $operation, RetypeApplicationContext $context): void
    {
        foreach ($this->metadataAppliers as $metadataApplier) {
            if ($metadataApplier->supports($operation)) {
                $metadataApplier->apply($operation, $context);
            }
        }
    }
}
