<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRetype\Application\Contract;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use PhpNoobs\PhpRetype\Domain\Retype\Plan\RetypePlan;
use PhpNoobs\PhpRetype\Domain\Retype\Request\EnumBackingTypeChangeRequest;

/**
 * Plans enum backing type changes.
 */
interface EnumBackingTypeChangePlannerInterface
{
    /**
     * Plans an enum backing type change.
     *
     * @param EnumBackingTypeChangeRequest $request the enum backing type change request
     * @param MemberDependencyGraphBuild   $build   the member graph build used to resolve declarations
     */
    public function plan(EnumBackingTypeChangeRequest $request, MemberDependencyGraphBuild $build): RetypePlan;
}
