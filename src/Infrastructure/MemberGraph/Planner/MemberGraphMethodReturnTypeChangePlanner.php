<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRetype\Infrastructure\MemberGraph\Planner;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use PhpNoobs\MemberGraph\Application\Source\Node\MemberGraphSourceNodeLocator;
use PhpNoobs\MemberGraph\Application\Source\Node\VirtualPhpSourceFileNodeMatchRole;
use PhpNoobs\PhpRetype\Application\Contract\MethodReturnTypeChangePlannerInterface;
use PhpNoobs\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnostic;
use PhpNoobs\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnosticCollection;
use PhpNoobs\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnosticSeverity;
use PhpNoobs\PhpRetype\Domain\Retype\Operation\RetypeOperation;
use PhpNoobs\PhpRetype\Domain\Retype\Operation\RetypeOperationCollection;
use PhpNoobs\PhpRetype\Domain\Retype\Operation\RetypeOperationRole;
use PhpNoobs\PhpRetype\Domain\Retype\Plan\RetypePlan;
use PhpNoobs\PhpRetype\Domain\Retype\Request\MethodReturnTypeChangeRequest;
use PhpNoobs\PhpRetype\Domain\Retype\Target\RetypeTargetKind;
use PhpParser\Node\Stmt\ClassMethod;

/**
 * Plans method return type changes from `member-graph` semantic facts.
 */
final readonly class MemberGraphMethodReturnTypeChangePlanner implements MethodReturnTypeChangePlannerInterface
{
    /**
     * Plans a method return type change.
     *
     * @param MethodReturnTypeChangeRequest $request the method return type change request
     * @param MemberDependencyGraphBuild    $build   the member graph build used to resolve declarations
     */
    public function plan(MethodReturnTypeChangeRequest $request, MemberDependencyGraphBuild $build): RetypePlan
    {
        $diagnostics = RetypeDiagnosticCollection::empty();
        $operations = RetypeOperationCollection::empty();

        $matches = MemberGraphSourceNodeLocator::fromBuild($build)
            ->method($request->className, $request->methodName);

        foreach ($matches as $match) {
            if (VirtualPhpSourceFileNodeMatchRole::MEMBER_DECLARATION !== $match->role) {
                continue;
            }

            if (!$match->node instanceof ClassMethod) {
                $diagnostics->add(new RetypeDiagnostic(
                    severity: RetypeDiagnosticSeverity::WARNING,
                    message: sprintf('Unsupported method return retype declaration node "%s".', $match->node::class),
                ));

                continue;
            }

            $operations->add(new RetypeOperation(
                targetKind: RetypeTargetKind::METHOD_RETURN,
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
                message: 'No method declaration source-node match was found for the requested method return type change.',
            ));
        }

        return new RetypePlan(
            request: $request,
            operations: $operations,
            diagnostics: $diagnostics,
        );
    }
}
