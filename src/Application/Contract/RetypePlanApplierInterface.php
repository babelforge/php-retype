<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRetype\Application\Contract;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use PhpNoobs\PhpRetype\Domain\Retype\Plan\RetypePlan;
use PhpNoobs\PhpRetype\Domain\Retype\Plan\RetypeResult;

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
