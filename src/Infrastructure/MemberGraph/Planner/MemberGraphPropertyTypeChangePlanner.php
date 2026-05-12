<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRetype\Infrastructure\MemberGraph\Planner;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use PhpNoobs\MemberGraph\Application\Source\Node\MemberGraphPropertyDeclarationContextItem;
use PhpNoobs\MemberGraph\Application\Source\Node\MemberGraphSourceNodeLocator;
use PhpNoobs\PhpRetype\Application\Contract\PropertyTypeChangePlannerInterface;
use PhpNoobs\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnostic;
use PhpNoobs\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnosticCollection;
use PhpNoobs\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnosticSeverity;
use PhpNoobs\PhpRetype\Domain\Retype\Operation\PropertyRetypeOperationContext;
use PhpNoobs\PhpRetype\Domain\Retype\Operation\RetypeOperation;
use PhpNoobs\PhpRetype\Domain\Retype\Operation\RetypeOperationCollection;
use PhpNoobs\PhpRetype\Domain\Retype\Operation\RetypeOperationRole;
use PhpNoobs\PhpRetype\Domain\Retype\Plan\RetypePlan;
use PhpNoobs\PhpRetype\Domain\Retype\Request\PropertyTypeChangeRequest;
use PhpNoobs\PhpRetype\Domain\Retype\Target\RetypeTargetKind;
use PhpParser\Node;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Property;

/**
 * Plans property type changes from `member-graph` structural declaration contexts.
 */
final readonly class MemberGraphPropertyTypeChangePlanner implements PropertyTypeChangePlannerInterface
{
    /**
     * Plans a property type change.
     *
     * @param PropertyTypeChangeRequest  $request the property type change request
     * @param MemberDependencyGraphBuild $build   the member graph build used to resolve declarations
     */
    public function plan(PropertyTypeChangeRequest $request, MemberDependencyGraphBuild $build): RetypePlan
    {
        $diagnostics = RetypeDiagnosticCollection::empty();
        $operations = RetypeOperationCollection::empty();
        $context = MemberGraphSourceNodeLocator::fromBuild($build)
            ->propertyDeclarationContext($request->className, $request->propertyNames);

        foreach ($context->diagnostics() as $diagnostic) {
            $diagnostics->add(new RetypeDiagnostic(
                severity: $this->mapSeverity($diagnostic->code),
                message: $diagnostic->message,
            ));
        }

        if ($diagnostics->hasErrors()) {
            return new RetypePlan($request, $operations, $diagnostics);
        }

        foreach ($context->items()->promoted() as $item) {
            $this->addPromotedOperation($request, $operations, $item);
        }

        foreach ($this->groupedItems($context->items()->grouped()->all()) as $items) {
            $this->addGroupedOperation($request, $operations, $items);
        }

        if (0 === count($operations)) {
            $diagnostics->add(new RetypeDiagnostic(
                severity: RetypeDiagnosticSeverity::WARNING,
                message: 'No property declaration context was found for the requested property type change.',
            ));
        }

        return new RetypePlan($request, $operations, $diagnostics);
    }

    /**
     * Adds one promoted property operation.
     *
     * @param PropertyTypeChangeRequest                 $request    the property type change request
     * @param RetypeOperationCollection                 $operations the operations being built
     * @param MemberGraphPropertyDeclarationContextItem $item       the promoted property item
     */
    private function addPromotedOperation(
        PropertyTypeChangeRequest $request,
        RetypeOperationCollection $operations,
        MemberGraphPropertyDeclarationContextItem $item,
    ): void {
        if (!$item->targetNode instanceof Param) {
            return;
        }

        $operations->add(new RetypeOperation(
            targetKind: RetypeTargetKind::PROPERTY,
            role: RetypeOperationRole::DECLARATION,
            file: $item->file,
            node: $item->targetNode,
            typeNode: $request->typeNode,
            docType: $request->docType,
            propertyContext: new PropertyRetypeOperationContext(
                parentClassLike: $item->parentClassLike,
                parentPropertyStatementIndex: null,
                targetPropertyNames: [$item->propertyName()],
                allSiblingsTargeted: true,
                phpDocOwner: $item->phpDocOwner,
            ),
        ));
    }

    /**
     * Adds one grouped property operation.
     *
     * @param PropertyTypeChangeRequest                       $request    the property type change request
     * @param RetypeOperationCollection                       $operations the operations being built
     * @param list<MemberGraphPropertyDeclarationContextItem> $items      the items from one grouped property declaration
     */
    private function addGroupedOperation(
        PropertyTypeChangeRequest $request,
        RetypeOperationCollection $operations,
        array $items,
    ): void {
        $first = $items[0] ?? null;

        if (null === $first || !$first->parentProperty instanceof Property) {
            return;
        }

        $operations->add(new RetypeOperation(
            targetKind: RetypeTargetKind::PROPERTY,
            role: RetypeOperationRole::DECLARATION,
            file: $first->file,
            node: $first->parentProperty,
            typeNode: $request->typeNode,
            docType: $request->docType,
            propertyContext: new PropertyRetypeOperationContext(
                parentClassLike: $first->parentClassLike,
                parentPropertyStatementIndex: $first->parentPropertyStatementIndex,
                targetPropertyNames: $this->propertyNames($items),
                allSiblingsTargeted: $first->allSiblingsTargeted,
                phpDocOwner: $this->sharedPhpDocOwner($items),
            ),
        ));
    }

    /**
     * Groups items by grouped property statement object.
     *
     * @param list<MemberGraphPropertyDeclarationContextItem> $items the grouped property items
     *
     * @return list<list<MemberGraphPropertyDeclarationContextItem>>
     */
    private function groupedItems(array $items): array
    {
        $groups = [];

        foreach ($items as $item) {
            if (null === $item->parentProperty) {
                continue;
            }

            $groups[spl_object_id($item->parentProperty)][] = $item;
        }

        return array_values($groups);
    }

    /**
     * Returns property names for context items.
     *
     * @param list<MemberGraphPropertyDeclarationContextItem> $items the items to inspect
     *
     * @return list<string>
     */
    private function propertyNames(array $items): array
    {
        return array_values(array_unique(array_map(
            static fn (MemberGraphPropertyDeclarationContextItem $item): string => $item->propertyName(),
            $items,
        )));
    }

    /**
     * Returns the shared PHPDoc owner when every item points to the same owner.
     *
     * @param list<MemberGraphPropertyDeclarationContextItem> $items the items to inspect
     */
    private function sharedPhpDocOwner(array $items): ?Node
    {
        $owner = null;

        foreach ($items as $item) {
            if (null === $item->phpDocOwner) {
                return null;
            }

            if (null === $owner) {
                $owner = $item->phpDocOwner;

                continue;
            }

            if ($owner !== $item->phpDocOwner) {
                return null;
            }
        }

        return $owner;
    }

    /**
     * Maps member-graph property context diagnostics to retype severities.
     *
     * @param string $code the member-graph diagnostic code
     */
    private function mapSeverity(string $code): RetypeDiagnosticSeverity
    {
        return match ($code) {
            'PROPERTIES_SPLIT_ACROSS_DECLARATIONS' => RetypeDiagnosticSeverity::ERROR,
            default => RetypeDiagnosticSeverity::WARNING,
        };
    }
}
