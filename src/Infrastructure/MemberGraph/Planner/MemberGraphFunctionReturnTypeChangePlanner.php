<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRetype\Infrastructure\MemberGraph\Planner;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use PhpNoobs\MemberGraph\Application\Source\Node\MemberGraphSourceNodeLocator;
use PhpNoobs\MemberGraph\Application\Source\Node\VirtualPhpSourceFileNodeMatchRole;
use PhpNoobs\PhpRetype\Application\Contract\FunctionReturnTypeChangePlannerInterface;
use PhpNoobs\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnostic;
use PhpNoobs\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnosticCollection;
use PhpNoobs\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnosticSeverity;
use PhpNoobs\PhpRetype\Domain\Retype\Operation\RetypeOperation;
use PhpNoobs\PhpRetype\Domain\Retype\Operation\RetypeOperationCollection;
use PhpNoobs\PhpRetype\Domain\Retype\Operation\RetypeOperationRole;
use PhpNoobs\PhpRetype\Domain\Retype\Plan\RetypePlan;
use PhpNoobs\PhpRetype\Domain\Retype\Request\FunctionReturnTypeChangeRequest;
use PhpNoobs\PhpRetype\Domain\Retype\Target\RetypeTargetKind;
use PhpParser\Node\Stmt\Function_;

/**
 * Plans function return type changes from `member-graph` semantic facts.
 */
final readonly class MemberGraphFunctionReturnTypeChangePlanner implements FunctionReturnTypeChangePlannerInterface
{
    /**
     * Plans a function return type change.
     *
     * @param FunctionReturnTypeChangeRequest $request the function return type change request
     * @param MemberDependencyGraphBuild      $build   the member graph build used to resolve declarations
     */
    public function plan(FunctionReturnTypeChangeRequest $request, MemberDependencyGraphBuild $build): RetypePlan
    {
        $diagnostics = RetypeDiagnosticCollection::empty();
        $operations = RetypeOperationCollection::empty();

        $matches = MemberGraphSourceNodeLocator::fromBuild($build)
            ->function($request->functionName);

        foreach ($matches as $match) {
            if (VirtualPhpSourceFileNodeMatchRole::MEMBER_DECLARATION !== $match->role) {
                continue;
            }

            if (!$match->node instanceof Function_) {
                $diagnostics->add(new RetypeDiagnostic(
                    severity: RetypeDiagnosticSeverity::WARNING,
                    message: sprintf('Unsupported function return retype declaration node "%s".', $match->node::class),
                ));

                continue;
            }

            $operations->add(new RetypeOperation(
                targetKind: RetypeTargetKind::FUNCTION_RETURN,
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
                message: 'No function declaration source-node match was found for the requested function return type change.',
            ));
        }

        return new RetypePlan(
            request: $request,
            operations: $operations,
            diagnostics: $diagnostics,
        );
    }
}
