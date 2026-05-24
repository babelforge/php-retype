<?php

declare(strict_types=1);

namespace BabelForge\PhpRetype\Application\Contract;

use BabelForge\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use BabelForge\PhpRetype\Domain\Retype\Plan\RetypePlan;
use BabelForge\PhpRetype\Domain\Retype\Request\FunctionParameterTypeChangeRequest;

/**
 * Plans function parameter type changes from an external semantic source.
 */
interface FunctionParameterTypeChangePlannerInterface
{
    /**
     * Plans a function parameter type change.
     *
     * @param FunctionParameterTypeChangeRequest $request the function parameter type change request
     * @param MemberDependencyGraphBuild         $build   the member graph build used to locate source nodes
     */
    public function plan(FunctionParameterTypeChangeRequest $request, MemberDependencyGraphBuild $build): RetypePlan;
}
