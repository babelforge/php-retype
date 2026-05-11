<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRetype\Application\Contract;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use PhpNoobs\PhpRetype\Domain\Retype\Plan\RetypePlan;
use PhpNoobs\PhpRetype\Domain\Retype\Request\MethodReturnTypeChangeRequest;

/**
 * Plans method return type changes from an external semantic source.
 */
interface MethodReturnTypeChangePlannerInterface
{
    /**
     * Plans a method return type change.
     *
     * @param MethodReturnTypeChangeRequest $request the method return type change request
     * @param MemberDependencyGraphBuild    $build   the member graph build used to locate source nodes
     */
    public function plan(MethodReturnTypeChangeRequest $request, MemberDependencyGraphBuild $build): RetypePlan;
}
