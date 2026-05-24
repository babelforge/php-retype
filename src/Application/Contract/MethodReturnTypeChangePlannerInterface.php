<?php

declare(strict_types=1);

namespace BabelForge\PhpRetype\Application\Contract;

use BabelForge\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use BabelForge\PhpRetype\Domain\Retype\Plan\RetypePlan;
use BabelForge\PhpRetype\Domain\Retype\Request\MethodReturnTypeChangeRequest;

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
