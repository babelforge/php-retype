<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRetype\Application\Contract;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use PhpNoobs\PhpRetype\Domain\Retype\Plan\RetypePlan;
use PhpNoobs\PhpRetype\Domain\Retype\Request\PropertyTypeChangeRequest;

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
