<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRetype\Domain\Retype\Step;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;

/**
 * Carries the member graph build used by one orchestrated retype step.
 */
final readonly class RetypeStepContext
{
    /**
     * Constructor.
     *
     * @param MemberDependencyGraphBuild $currentBuild the current member graph build
     */
    public function __construct(public MemberDependencyGraphBuild $currentBuild)
    {
    }

    /**
     * Creates a step context from a member graph build.
     *
     * @param MemberDependencyGraphBuild $build the member graph build
     */
    public static function fromBuild(MemberDependencyGraphBuild $build): self
    {
        return new self($build);
    }
}
