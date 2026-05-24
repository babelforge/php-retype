<?php

declare(strict_types=1);

namespace BabelForge\PhpRetype\Application\Contract;

use BabelForge\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use BabelForge\PhpRetype\Domain\Retype\Plan\RetypePlan;
use BabelForge\PhpRetype\Domain\Retype\Request\ClassConstantTypeChangeRequest;

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
