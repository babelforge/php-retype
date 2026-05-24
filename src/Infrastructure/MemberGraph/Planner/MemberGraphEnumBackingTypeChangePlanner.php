<?php

declare(strict_types=1);

namespace BabelForge\PhpRetype\Infrastructure\MemberGraph\Planner;

use BabelForge\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use BabelForge\MemberGraph\Application\Source\Node\MemberGraphSourceNodeLocator;
use BabelForge\MemberGraph\Application\Source\Node\VirtualPhpSourceFileNodeMatchRole;
use BabelForge\PhpRetype\Application\Contract\EnumBackingTypeChangePlannerInterface;
use BabelForge\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnostic;
use BabelForge\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnosticCollection;
use BabelForge\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnosticSeverity;
use BabelForge\PhpRetype\Domain\Retype\Operation\RetypeOperation;
use BabelForge\PhpRetype\Domain\Retype\Operation\RetypeOperationCollection;
use BabelForge\PhpRetype\Domain\Retype\Operation\RetypeOperationRole;
use BabelForge\PhpRetype\Domain\Retype\Plan\RetypePlan;
use BabelForge\PhpRetype\Domain\Retype\Request\EnumBackingTypeChangeRequest;
use BabelForge\PhpRetype\Domain\Retype\Target\RetypeTargetKind;
use PhpParser\Node\Stmt\Enum_;

/**
 * Plans enum backing type changes from `member-graph` owner declaration facts.
 */
final readonly class MemberGraphEnumBackingTypeChangePlanner implements EnumBackingTypeChangePlannerInterface
{
    /**
     * Plans an enum backing type change.
     *
     * @param EnumBackingTypeChangeRequest $request the enum backing type change request
     * @param MemberDependencyGraphBuild   $build   the member graph build used to resolve declarations
     */
    public function plan(EnumBackingTypeChangeRequest $request, MemberDependencyGraphBuild $build): RetypePlan
    {
        $diagnostics = RetypeDiagnosticCollection::empty();
        $operations = RetypeOperationCollection::empty();

        $matches = MemberGraphSourceNodeLocator::fromBuild($build)
            ->owner($request->enumName);

        foreach ($matches as $match) {
            if (VirtualPhpSourceFileNodeMatchRole::OWNER_DECLARATION !== $match->role) {
                continue;
            }

            if (!$match->node instanceof Enum_) {
                $diagnostics->add(new RetypeDiagnostic(
                    severity: RetypeDiagnosticSeverity::WARNING,
                    message: sprintf('Unsupported enum backing retype declaration node "%s".', $match->node::class),
                ));

                continue;
            }

            $operations->add(new RetypeOperation(
                targetKind: RetypeTargetKind::ENUM_BACKING,
                role: RetypeOperationRole::DECLARATION,
                file: $match->virtualFile,
                node: $match->node,
                typeNode: $request->typeNode,
                docType: null,
            ));
        }

        if (0 === count($operations)) {
            $diagnostics->add(new RetypeDiagnostic(
                severity: RetypeDiagnosticSeverity::WARNING,
                message: 'No enum declaration source-node match was found for the requested enum backing type change.',
            ));
        }

        return new RetypePlan(
            request: $request,
            operations: $operations,
            diagnostics: $diagnostics,
        );
    }
}
