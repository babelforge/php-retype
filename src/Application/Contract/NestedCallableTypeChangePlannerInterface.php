<?php

declare(strict_types=1);

namespace BabelForge\PhpRetype\Application\Contract;

use BabelForge\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use BabelForge\PhpRetype\Domain\Retype\Plan\RetypePlan;
use BabelForge\PhpRetype\Domain\Retype\Request\NestedCallableTypeChangeRequest;

/**
 * Plans nested callable type changes.
 */
interface NestedCallableTypeChangePlannerInterface
{
    /**
     * Plans a nested callable type change.
     *
     * @param NestedCallableTypeChangeRequest $request the nested callable type change request
     * @param MemberDependencyGraphBuild      $build   the member graph build
     */
    public function plan(NestedCallableTypeChangeRequest $request, MemberDependencyGraphBuild $build): RetypePlan;
}
