<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRetype\Application\Contract;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use PhpNoobs\PhpRetype\Domain\Retype\Plan\RetypePlan;
use PhpNoobs\PhpRetype\Domain\Retype\Request\ClassConstantTypeChangeRequest;

/**
 * Plans class constant type changes.
 */
interface ClassConstantTypeChangePlannerInterface
{
    /**
     * Plans a class constant type change.
     *
     * @param ClassConstantTypeChangeRequest $request the class constant type change request
     * @param MemberDependencyGraphBuild     $build   the member graph build used to resolve declarations
     */
    public function plan(ClassConstantTypeChangeRequest $request, MemberDependencyGraphBuild $build): RetypePlan;
}
