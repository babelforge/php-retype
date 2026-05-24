<?php

declare(strict_types=1);

namespace BabelForge\PhpRetype\Application\Contract;

use BabelForge\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use BabelForge\PhpRetype\Domain\Retype\Plan\RetypePlan;
use BabelForge\PhpRetype\Domain\Retype\Request\PropertyTypeChangeRequest;

/**
 * Plans property type changes from an external semantic source.
 */
interface PropertyTypeChangePlannerInterface
{
    /**
     * Plans a property type change.
     *
     * @param PropertyTypeChangeRequest  $request the property type change request
     * @param MemberDependencyGraphBuild $build   the member graph build used to locate source nodes
     */
    public function plan(PropertyTypeChangeRequest $request, MemberDependencyGraphBuild $build): RetypePlan;
}
