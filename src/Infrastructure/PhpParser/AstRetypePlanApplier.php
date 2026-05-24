<?php

declare(strict_types=1);

namespace BabelForge\PhpRetype\Infrastructure\PhpParser;

use BabelForge\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use BabelForge\PhpRetype\Application\Contract\RetypePlanApplierInterface;
use BabelForge\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnostic;
use BabelForge\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnosticCollection;
use BabelForge\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnosticSeverity;
use BabelForge\PhpRetype\Domain\Retype\Operation\RetypeOperation;
use BabelForge\PhpRetype\Domain\Retype\Plan\RetypePlan;
use BabelForge\PhpRetype\Domain\Retype\Plan\RetypeResult;
use BabelForge\PhpRetype\Infrastructure\PhpParser\Application\RetypeApplicationContext;
use BabelForge\PhpRetype\Infrastructure\PhpParser\Application\RetypeMetadataApplierInterface;
use BabelForge\PhpRetype\Infrastructure\PhpParser\Application\RetypeNodeApplierInterface;
use BabelForge\PhpRetype\Infrastructure\PhpParser\Callable_\CallableReturnTypeNodeApplier;
use BabelForge\PhpRetype\Infrastructure\PhpParser\ClassConstant\ClassConstantTypeNodeApplier;
use BabelForge\PhpRetype\Infrastructure\PhpParser\Docblock\ParameterDocblockTypeApplier;
use BabelForge\PhpRetype\Infrastructure\PhpParser\Docblock\ReturnDocblockTypeApplier;
use BabelForge\PhpRetype\Infrastructure\PhpParser\Docblock\VarDocblockTypeApplier;
use BabelForge\PhpRetype\Infrastructure\PhpParser\Enum_\EnumBackingTypeNodeApplier;
use BabelForge\PhpRetype\Infrastructure\PhpParser\Function_\FunctionReturnTypeNodeApplier;
use BabelForge\PhpRetype\Infrastructure\PhpParser\Method\MethodReturnTypeNodeApplier;
use BabelForge\PhpRetype\Infrastructure\PhpParser\Parameter\ParameterTypeNodeApplier;
use BabelForge\PhpRetype\Infrastructure\PhpParser\Property\PropertyTypeNodeApplier;
use BabelForge\PhpSource\VirtualPhpSourceFile;

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
