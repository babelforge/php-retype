<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRetype\Application\Contract;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use PhpNoobs\PhpRetype\Domain\Retype\Plan\RetypePlan;
use PhpNoobs\PhpRetype\Domain\Retype\Request\NestedCallableTypeChangeRequest;

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
