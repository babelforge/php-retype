<?php

declare(strict_types=1);

namespace BabelForge\PhpRetype\Application\Contract;

use BabelForge\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use BabelForge\PhpRetype\Domain\Retype\Plan\RetypePlan;
use BabelForge\PhpRetype\Domain\Retype\Request\FunctionReturnTypeChangeRequest;

/**
 * Plans function return type changes from an external semantic source.
 */
interface FunctionReturnTypeChangePlannerInterface
{
    /**
     * Plans a function return type change.
     *
     * @param FunctionReturnTypeChangeRequest $request the function return type change request
     * @param MemberDependencyGraphBuild      $build   the member graph build used to locate source nodes
     */
    public function plan(FunctionReturnTypeChangeRequest $request, MemberDependencyGraphBuild $build): RetypePlan;
}
