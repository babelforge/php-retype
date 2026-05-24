<?php

declare(strict_types=1);

namespace BabelForge\PhpRetype\Infrastructure\MemberGraph\Planner;

use BabelForge\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use BabelForge\MemberGraph\Application\Source\Node\MemberGraphSourceNodeLocator;
use BabelForge\MemberGraph\Application\Source\Node\VirtualPhpSourceFileNodeMatchRole;
use BabelForge\PhpRetype\Application\Contract\FunctionParameterTypeChangePlannerInterface;
use BabelForge\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnostic;
use BabelForge\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnosticCollection;
use BabelForge\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnosticSeverity;
use BabelForge\PhpRetype\Domain\Retype\Operation\RetypeOperation;
use BabelForge\PhpRetype\Domain\Retype\Operation\RetypeOperationCollection;
use BabelForge\PhpRetype\Domain\Retype\Operation\RetypeOperationRole;
use BabelForge\PhpRetype\Domain\Retype\Plan\RetypePlan;
use BabelForge\PhpRetype\Domain\Retype\Request\FunctionParameterTypeChangeRequest;
use BabelForge\PhpRetype\Domain\Retype\Target\RetypeTargetKind;

/**
 * Plans function parameter type changes from `member-graph` semantic facts.
 */
final readonly class MemberGraphFunctionParameterTypeChangePlanner implements FunctionParameterTypeChangePlannerInterface
{
    /**
     * Plans a function parameter type change.
     *
     * @param FunctionParameterTypeChangeRequest $request the function parameter type change request
     * @param MemberDependencyGraphBuild         $build   the member graph build used to resolve declarations
     */
    public function plan(FunctionParameterTypeChangeRequest $request, MemberDependencyGraphBuild $build): RetypePlan
    {
        $diagnostics = RetypeDiagnosticCollection::empty();
        $operations = RetypeOperationCollection::empty();

        $matches = MemberGraphSourceNodeLocator::fromBuild($build)
            ->parameter('', $request->functionName, $request->parameterName, $request->parameterIndex);

        foreach ($matches as $match) {
            if (VirtualPhpSourceFileNodeMatchRole::PARAMETER_DECLARATION !== $match->role) {
                continue;
            }

            $operations->add(new RetypeOperation(
                targetKind: RetypeTargetKind::FUNCTION_PARAMETER,
                role: RetypeOperationRole::DECLARATION,
                file: $match->virtualFile,
                node: $match->node,
                typeNode: $request->typeNode,
                docType: $request->docType,
            ));
        }

        if (0 === count($operations)) {
            $diagnostics->add(new RetypeDiagnostic(
                severity: RetypeDiagnosticSeverity::WARNING,
                message: 'No parameter declaration source-node match was found for the requested function parameter type change.',
            ));
        }

        return new RetypePlan(
            request: $request,
            operations: $operations,
            diagnostics: $diagnostics,
        );
    }
}
