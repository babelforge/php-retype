<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRetype\Infrastructure\PhpParser\Property;

use PhpNoobs\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnostic;
use PhpNoobs\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnosticSeverity;
use PhpNoobs\PhpRetype\Domain\Retype\Operation\PropertyRetypeOperationContext;
use PhpNoobs\PhpRetype\Domain\Retype\Operation\RetypeOperation;
use PhpNoobs\PhpRetype\Domain\Retype\Target\RetypeTargetKind;
use PhpNoobs\PhpRetype\Infrastructure\PhpParser\Application\RetypeApplicationContext;
use PhpNoobs\PhpRetype\Infrastructure\PhpParser\Application\RetypeNodeApplierInterface;
use PhpParser\Node;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\PropertyItem;
use PhpParser\Node\Stmt\Property;

/**
 * Applies property type changes to grouped properties and promoted property parameters.
 */
final readonly class PropertyTypeNodeApplier implements RetypeNodeApplierInterface
{
    /**
     * Indicates whether this applier supports the retype operation.
     *
     * @param RetypeOperation $operation the retype operation to inspect
     */
    public function supports(RetypeOperation $operation): bool
    {
        return RetypeTargetKind::PROPERTY === $operation->targetKind;
    }

    /**
     * Applies one property type change operation.
     *
     * @param RetypeOperation          $operation the retype operation to apply
     * @param RetypeApplicationContext $context   the retype application context
     */
    public function apply(RetypeOperation $operation, RetypeApplicationContext $context): bool
    {
        if ($operation->node instanceof Param) {
            $operation->node->type = null === $operation->typeNode ? null : clone $operation->typeNode;

            return true;
        }

        if (!$operation->node instanceof Property) {
            $context->diagnostics->add(new RetypeDiagnostic(
                severity: RetypeDiagnosticSeverity::WARNING,
                message: sprintf('Unsupported property retype node "%s".', $operation->node::class),
            ));

            return false;
        }

        if (null === $operation->propertyContext) {
            $context->diagnostics->add(new RetypeDiagnostic(
                severity: RetypeDiagnosticSeverity::WARNING,
                message: 'Missing property declaration context.',
            ));

            return false;
        }

        if ($operation->propertyContext->allSiblingsTargeted) {
            $operation->node->type = null === $operation->typeNode ? null : clone $operation->typeNode;

            return true;
        }

        return $this->applyPartialGroupedPropertyTypeChange($operation, $operation->propertyContext, $context);
    }

    /**
     * Applies a partial grouped property type change by splitting the grouped declaration.
     *
     * @param RetypeOperation                $operation       the retype operation to apply
     * @param PropertyRetypeOperationContext $propertyContext the property operation context
     * @param RetypeApplicationContext       $context         the retype application context
     */
    private function applyPartialGroupedPropertyTypeChange(
        RetypeOperation $operation,
        PropertyRetypeOperationContext $propertyContext,
        RetypeApplicationContext $context,
    ): bool {
        if (!$operation->node instanceof Property || null === $propertyContext->parentClassLike || null === $propertyContext->parentPropertyStatementIndex) {
            $context->diagnostics->add(new RetypeDiagnostic(
                severity: RetypeDiagnosticSeverity::WARNING,
                message: 'Incomplete grouped property declaration context.',
            ));

            return false;
        }

        $targetProperties = [];
        $remainingProperties = [];

        foreach ($operation->node->props as $propertyProperty) {
            if ($propertyContext->targets($propertyProperty->name->toString())) {
                $targetProperties[] = $propertyProperty;

                continue;
            }

            $remainingProperties[] = clone $propertyProperty;
        }

        if ([] === $targetProperties) {
            $context->diagnostics->add(new RetypeDiagnostic(
                severity: RetypeDiagnosticSeverity::WARNING,
                message: 'No targeted property remained in the grouped declaration.',
            ));

            return false;
        }

        $originalType = $this->propertyType($operation->node->type);
        $remainingProperty = [] === $remainingProperties ? null : $this->createRemainingProperty(
            originalProperty: $operation->node,
            remainingProperties: $remainingProperties,
            originalType: $originalType,
        );
        $operation->node->props = $targetProperties;
        $operation->node->type = null === $operation->typeNode ? null : clone $operation->typeNode;

        if (null === $remainingProperty) {
            return true;
        }

        array_splice(
            array: $propertyContext->parentClassLike->stmts,
            offset: $propertyContext->parentPropertyStatementIndex + 1,
            length: 0,
            replacement: [$remainingProperty],
        );

        return true;
    }

    /**
     * Creates the remaining grouped property statement after a split.
     *
     * @param Property                         $originalProperty    the original property statement
     * @param list<PropertyItem>               $remainingProperties the properties that keep the original type
     * @param Identifier|Name|ComplexType|null $originalType        the original property native type
     */
    private function createRemainingProperty(
        Property $originalProperty,
        array $remainingProperties,
        Identifier|Name|ComplexType|null $originalType,
    ): Property {
        $attributes = $this->copyAttributes($originalProperty);

        return new Property(
            flags: $originalProperty->flags,
            props: $remainingProperties,
            attributes: $attributes,
            type: null === $originalType ? null : clone $originalType,
            attrGroups: $this->cloneNodes($originalProperty->attrGroups),
            hooks: $this->cloneNodes($originalProperty->hooks),
        );
    }

    /**
     * Returns a property type node when the value is supported by PHPParser properties.
     *
     * @param Node|null $type the raw property type
     */
    private function propertyType(?Node $type): Identifier|Name|ComplexType|null
    {
        if ($type instanceof Identifier || $type instanceof Name || $type instanceof ComplexType) {
            return $type;
        }

        return null;
    }

    /**
     * Copies node attributes.
     *
     * @param Node $node the node to inspect
     *
     * @return array<string, mixed>
     */
    private function copyAttributes(Node $node): array
    {
        return $node->getAttributes();
    }

    /**
     * Clones a list of PHPParser nodes.
     *
     * @template T of Node
     *
     * @param array<array-key, T> $nodes the nodes to clone
     *
     * @return array<array-key, T>
     */
    private function cloneNodes(array $nodes): array
    {
        return array_map(static fn (Node $node): Node => clone $node, $nodes);
    }
}
