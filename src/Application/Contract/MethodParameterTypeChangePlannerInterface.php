<?php

declare(strict_types=1);

namespace BabelForge\PhpRetype\Application\Contract;

use BabelForge\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use BabelForge\PhpRetype\Domain\Retype\Plan\RetypePlan;
use BabelForge\PhpRetype\Domain\Retype\Request\MethodParameterTypeChangeRequest;

/**
 * Plans method parameter type changes from an external semantic source.
 */
interface MethodParameterTypeChangePlannerInterface
{
    /**
     * Plans a method parameter type change.
     *
     * @param MethodParameterTypeChangeRequest $request the method parameter type change request
     * @param MemberDependencyGraphBuild       $build   the member graph build used to locate source nodes
     */
    public function plan(MethodParameterTypeChangeRequest $request, MemberDependencyGraphBuild $build): RetypePlan;
}
