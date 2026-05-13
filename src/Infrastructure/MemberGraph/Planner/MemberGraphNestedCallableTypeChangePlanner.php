<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRetype\Infrastructure\MemberGraph\Planner;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use PhpNoobs\MemberGraph\Application\Source\Node\MemberGraphSourceNodeLocator;
use PhpNoobs\MemberGraph\Application\Source\Node\VirtualPhpSourceFileNodeMatchRole;
use PhpNoobs\PhpRetype\Application\Contract\NestedCallableTypeChangePlannerInterface;
use PhpNoobs\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnostic;
use PhpNoobs\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnosticCollection;
use PhpNoobs\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnosticSeverity;
use PhpNoobs\PhpRetype\Domain\Retype\Operation\RetypeOperation;
use PhpNoobs\PhpRetype\Domain\Retype\Operation\RetypeOperationCollection;
use PhpNoobs\PhpRetype\Domain\Retype\Operation\RetypeOperationRole;
use PhpNoobs\PhpRetype\Domain\Retype\Plan\RetypePlan;
use PhpNoobs\PhpRetype\Domain\Retype\Request\NestedCallableContainerKind;
use PhpNoobs\PhpRetype\Domain\Retype\Request\NestedCallableKind;
use PhpNoobs\PhpRetype\Domain\Retype\Request\NestedCallableTargetKind;
use PhpNoobs\PhpRetype\Domain\Retype\Request\NestedCallableTypeChangeRequest;
use PhpNoobs\PhpRetype\Domain\Retype\Target\RetypeTargetKind;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;

/**
 * Plans nested callable type changes from member-graph containers or virtual files.
 */
final readonly class MemberGraphNestedCallableTypeChangePlanner implements NestedCallableTypeChangePlannerInterface
{
    /**
     * Plans a nested callable type change.
     *
     * @param NestedCallableTypeChangeRequest $request the nested callable type change request
     * @param MemberDependencyGraphBuild      $build   the member graph build
     */
    public function plan(NestedCallableTypeChangeRequest $request, MemberDependencyGraphBuild $build): RetypePlan
    {
        $operations = RetypeOperationCollection::empty();
        $diagnostics = RetypeDiagnosticCollection::empty();
        $container = $this->container($request, $build, $diagnostics);

        if (null === $container) {
            return new RetypePlan($request, $operations, $diagnostics);
        }

        $callable = $this->nestedCallable($container->node, $request);

        if (null === $callable) {
            $diagnostics->add(new RetypeDiagnostic(
                severity: RetypeDiagnosticSeverity::WARNING,
                message: sprintf('Nested %s at index %d was not found in the requested %s container.', $this->callableLabel($request), $request->callableIndex, strtolower($request->containerKind->name)),
            ));

            return new RetypePlan($request, $operations, $diagnostics);
        }

        $operationNode = $this->operationNode($callable, $request, $diagnostics);

        if (null === $operationNode) {
            return new RetypePlan($request, $operations, $diagnostics);
        }

        $operations->add(new RetypeOperation(
            targetKind: $this->targetKind($request),
            role: RetypeOperationRole::DECLARATION,
            file: $container->file,
            node: $operationNode,
            typeNode: $request->typeNode,
            docType: $request->docType,
        ));

        return new RetypePlan($request, $operations, $diagnostics);
    }

    /**
     * Resolves the container node and virtual file.
     *
     * @param NestedCallableTypeChangeRequest $request     the nested callable request
     * @param MemberDependencyGraphBuild      $build       the member graph build
     * @param RetypeDiagnosticCollection      $diagnostics the diagnostics to append to
     */
    private function container(
        NestedCallableTypeChangeRequest $request,
        MemberDependencyGraphBuild $build,
        RetypeDiagnosticCollection $diagnostics,
    ): ?NestedCallableContainer {
        if (NestedCallableContainerKind::FILE === $request->containerKind) {
            return $this->fileContainer($request, $build, $diagnostics);
        }

        $matches = NestedCallableContainerKind::METHOD === $request->containerKind
            ? MemberGraphSourceNodeLocator::fromBuild($build)->method((string) $request->className, (string) $request->methodName)
            : MemberGraphSourceNodeLocator::fromBuild($build)->function((string) $request->functionName);

        foreach ($matches as $match) {
            if (VirtualPhpSourceFileNodeMatchRole::MEMBER_DECLARATION !== $match->role) {
                continue;
            }

            if (NestedCallableContainerKind::METHOD === $request->containerKind && !$match->node instanceof ClassMethod) {
                continue;
            }

            if (NestedCallableContainerKind::FUNCTION === $request->containerKind && !$match->node instanceof Function_) {
                continue;
            }

            return new NestedCallableContainer($match->virtualFile, $match->node);
        }

        $diagnostics->add(new RetypeDiagnostic(
            severity: RetypeDiagnosticSeverity::WARNING,
            message: sprintf('No %s container was found for the requested nested callable type change.', strtolower($request->containerKind->name)),
        ));

        return null;
    }

    /**
     * Resolves a file container from the build virtual files.
     *
     * @param NestedCallableTypeChangeRequest $request     the nested callable request
     * @param MemberDependencyGraphBuild      $build       the member graph build
     * @param RetypeDiagnosticCollection      $diagnostics the diagnostics to append to
     */
    private function fileContainer(
        NestedCallableTypeChangeRequest $request,
        MemberDependencyGraphBuild $build,
        RetypeDiagnosticCollection $diagnostics,
    ): ?NestedCallableContainer {
        $filePath = (string) $request->filePath;
        $realPath = realpath($filePath);
        $realFilePath = false === $realPath ? $filePath : $realPath;

        foreach ($build->virtualFiles as $virtualFile) {
            if ($virtualFile->fullFilePath !== $realFilePath && $virtualFile->virtualFilePath !== $filePath) {
                continue;
            }

            return new NestedCallableContainer($virtualFile, array_values($virtualFile->nodes));
        }

        $diagnostics->add(new RetypeDiagnostic(
            severity: RetypeDiagnosticSeverity::WARNING,
            message: sprintf('File container "%s" was not found for the requested nested callable type change.', (string) $request->filePath),
        ));

        return null;
    }

    /**
     * Finds a nested callable by deterministic DFS index.
     *
     * @param Node|list<Node>                 $container the container node or nodes
     * @param NestedCallableTypeChangeRequest $request   the nested callable request
     */
    private function nestedCallable(Node|array $container, NestedCallableTypeChangeRequest $request): Closure|ArrowFunction|null
    {
        $matches = $this->collectNestedCallables($container, $request->callableKind);

        return $matches[$request->callableIndex] ?? null;
    }

    /**
     * Collects nested callables in DFS order.
     *
     * @param Node|array<array-key, mixed> $nodeOrNodes the node or node list to inspect
     * @param NestedCallableKind           $kind        the callable kind to collect
     *
     * @return list<Closure|ArrowFunction>
     */
    private function collectNestedCallables(Node|array $nodeOrNodes, NestedCallableKind $kind): array
    {
        $nodes = $nodeOrNodes instanceof Node ? [$nodeOrNodes] : $nodeOrNodes;
        $matches = [];

        foreach ($nodes as $node) {
            if (!$node instanceof Node) {
                continue;
            }

            if (NestedCallableKind::CLOSURE === $kind && $node instanceof Closure) {
                $matches[] = $node;
            }

            if (NestedCallableKind::ARROW_FUNCTION === $kind && $node instanceof ArrowFunction) {
                $matches[] = $node;
            }

            foreach (get_object_vars($node) as $subNode) {
                if ($subNode instanceof Node || is_array($subNode)) {
                    array_push($matches, ...$this->collectNestedCallables($subNode, $kind));
                }
            }
        }

        return $matches;
    }

    /**
     * Resolves the operation node for the requested nested callable target.
     *
     * @param Closure|ArrowFunction           $callable    the nested callable node
     * @param NestedCallableTypeChangeRequest $request     the nested callable request
     * @param RetypeDiagnosticCollection      $diagnostics the diagnostics to append to
     */
    private function operationNode(
        Closure|ArrowFunction $callable,
        NestedCallableTypeChangeRequest $request,
        RetypeDiagnosticCollection $diagnostics,
    ): FunctionLike|Param|null {
        if (NestedCallableTargetKind::RETURN === $request->targetKind) {
            return $callable;
        }

        foreach ($callable->getParams() as $index => $parameter) {
            if (null !== $request->parameterIndex && $index !== $request->parameterIndex) {
                continue;
            }

            if (null !== $request->parameterName && $this->parameterName($parameter) !== $request->parameterName) {
                continue;
            }

            return $parameter;
        }

        $diagnostics->add(new RetypeDiagnostic(
            severity: RetypeDiagnosticSeverity::WARNING,
            message: $this->missingParameterMessage($request),
        ));

        return null;
    }

    /**
     * Returns a diagnostic message for a missing nested callable parameter.
     *
     * @param NestedCallableTypeChangeRequest $request the nested callable request
     */
    private function missingParameterMessage(NestedCallableTypeChangeRequest $request): string
    {
        if (null !== $request->parameterName && null !== $request->parameterIndex) {
            return sprintf('Nested callable parameter "%s" at index %d was not found for the requested type change.', $request->parameterName, $request->parameterIndex);
        }

        if (null !== $request->parameterName) {
            return sprintf('Nested callable parameter "%s" was not found for the requested type change.', $request->parameterName);
        }

        if (null !== $request->parameterIndex) {
            return sprintf('Nested callable parameter at index %d was not found for the requested type change.', $request->parameterIndex);
        }

        return 'Nested callable parameter was not found for the requested type change.';
    }

    /**
     * Returns the parameter name without "$".
     *
     * @param Param $parameter the parameter declaration
     */
    private function parameterName(Param $parameter): ?string
    {
        if (!$parameter->var instanceof Variable || !is_string($parameter->var->name)) {
            return null;
        }

        return $parameter->var->name;
    }

    /**
     * Returns the retype target kind for the nested callable request.
     *
     * @param NestedCallableTypeChangeRequest $request the nested callable request
     */
    private function targetKind(NestedCallableTypeChangeRequest $request): RetypeTargetKind
    {
        if (NestedCallableKind::CLOSURE === $request->callableKind) {
            return NestedCallableTargetKind::PARAMETER === $request->targetKind
                ? RetypeTargetKind::CLOSURE_PARAMETER
                : RetypeTargetKind::CLOSURE_RETURN;
        }

        return NestedCallableTargetKind::PARAMETER === $request->targetKind
            ? RetypeTargetKind::ARROW_FUNCTION_PARAMETER
            : RetypeTargetKind::ARROW_FUNCTION_RETURN;
    }

    /**
     * Returns a lowercase callable label for diagnostics.
     *
     * @param NestedCallableTypeChangeRequest $request the nested callable request
     */
    private function callableLabel(NestedCallableTypeChangeRequest $request): string
    {
        return NestedCallableKind::CLOSURE === $request->callableKind ? 'closure' : 'arrow function';
    }
}
