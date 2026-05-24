<?php

declare(strict_types=1);

namespace BabelForge\PhpRetype\Application\Contract;

use BabelForge\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use BabelForge\PhpRetype\Domain\Retype\Plan\RetypePlan;
use BabelForge\PhpRetype\Domain\Retype\Plan\RetypeResult;

/**
 * Applies planned retype operations to virtual source files.
 */
interface RetypePlanApplierInterface
{
    /**
     * Applies a retype plan.
     *
     * @param RetypePlan                 $plan  the retype plan to apply
     * @param MemberDependencyGraphBuild $build the member graph build containing virtual files
     */
    public function apply(RetypePlan $plan, MemberDependencyGraphBuild $build): RetypeResult;
}
