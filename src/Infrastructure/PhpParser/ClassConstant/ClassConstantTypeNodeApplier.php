<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRetype\Infrastructure\PhpParser\ClassConstant;

use PhpNoobs\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnostic;
use PhpNoobs\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnosticSeverity;
use PhpNoobs\PhpRetype\Domain\Retype\Operation\RetypeOperation;
use PhpNoobs\PhpRetype\Domain\Retype\Target\RetypeTargetKind;
use PhpNoobs\PhpRetype\Infrastructure\PhpParser\Application\RetypeApplicationContext;
use PhpNoobs\PhpRetype\Infrastructure\PhpParser\Application\RetypeNodeApplierInterface;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Const_;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassLike;

/**
 * Applies class constant type changes to class constant declaration nodes.
 */
final readonly class ClassConstantTypeNodeApplier implements RetypeNodeApplierInterface
{
    /**
     * Indicates whether this applier supports the retype operation.
     *
     * @param RetypeOperation $operation the retype operation to inspect
     */
    public function supports(RetypeOperation $operation): bool
    {
        return RetypeTargetKind::CLASS_CONSTANT === $operation->targetKind;
    }

    /**
     * Applies one class constant type change operation.
     *
     * @param RetypeOperation          $operation the retype operation to apply
     * @param RetypeApplicationContext $context   the retype application context
     */
    public function apply(RetypeOperation $operation, RetypeApplicationContext $context): bool
    {
        if (!$operation->node instanceof Const_) {
            $context->diagnostics->add(new RetypeDiagnostic(
                severity: RetypeDiagnosticSeverity::WARNING,
                message: sprintf('Unsupported class constant retype node "%s".', $operation->node::class),
            ));

            return false;
        }

        $parentClassConst = $operation->node->getAttribute('parent');

        if (!$parentClassConst instanceof ClassConst) {
            $context->diagnostics->add(new RetypeDiagnostic(
                severity: RetypeDiagnosticSeverity::WARNING,
                message: 'Missing parent class constant declaration context.',
            ));

            return false;
        }

        if (1 === count($parentClassConst->consts)) {
            $parentClassConst->type = null === $operation->typeNode ? null : clone $operation->typeNode;
            $this->changeVarTagType($parentClassConst, $operation->docType);

            return true;
        }

        return $this->applyGroupedClassConstantTypeChange($operation, $parentClassConst, $context);
    }

    /**
     * Applies a grouped class constant type change by splitting the declaration.
     *
     * @param RetypeOperation          $operation        the retype operation
     * @param ClassConst               $parentClassConst the parent class constant statement
     * @param RetypeApplicationContext $context          the retype application context
     */
    private function applyGroupedClassConstantTypeChange(
        RetypeOperation $operation,
        ClassConst $parentClassConst,
        RetypeApplicationContext $context,
    ): bool {
        $parentClassLike = $parentClassConst->getAttribute('parent');

        if (!$parentClassLike instanceof ClassLike) {
            $context->diagnostics->add(new RetypeDiagnostic(
                severity: RetypeDiagnosticSeverity::WARNING,
                message: 'Missing parent class-like declaration context.',
            ));

            return false;
        }

        $parentStatementIndex = $this->statementIndex($parentClassLike, $parentClassConst);

        if (null === $parentStatementIndex) {
            $context->diagnostics->add(new RetypeDiagnostic(
                severity: RetypeDiagnosticSeverity::WARNING,
                message: 'Missing class constant statement index.',
            ));

            return false;
        }

        $targetConstants = [];
        $remainingConstants = [];

        foreach ($parentClassConst->consts as $constant) {
            if ($constant === $operation->node) {
                $targetConstants[] = $constant;

                continue;
            }

            $remainingConstants[] = clone $constant;
        }

        if ([] === $targetConstants) {
            $context->diagnostics->add(new RetypeDiagnostic(
                severity: RetypeDiagnosticSeverity::WARNING,
                message: 'No targeted class constant remained in the grouped declaration.',
            ));

            return false;
        }

        $originalType = $this->classConstantType($parentClassConst->type);
        $remainingClassConst = new ClassConst(
            consts: $remainingConstants,
            flags: $parentClassConst->flags,
            attributes: $this->copyAttributes($parentClassConst),
            attrGroups: $this->cloneNodes($parentClassConst->attrGroups),
            type: null === $originalType ? null : clone $originalType,
        );

        $parentClassConst->consts = $targetConstants;
        $parentClassConst->type = null === $operation->typeNode ? null : clone $operation->typeNode;
        $this->changeVarTagType($parentClassConst, $operation->docType);

        if ([] !== $remainingConstants) {
            array_splice(
                array: $parentClassLike->stmts,
                offset: $parentStatementIndex + 1,
                length: 0,
                replacement: [$remainingClassConst],
            );
        }

        return true;
    }

    /**
     * Returns the index of one statement in a class-like declaration.
     *
     * @param ClassLike  $classLike the class-like declaration
     * @param ClassConst $statement the class constant statement to find
     */
    private function statementIndex(ClassLike $classLike, ClassConst $statement): ?int
    {
        foreach ($classLike->stmts as $index => $classStatement) {
            if ($classStatement === $statement) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Returns a class constant type node when the value is supported.
     *
     * @param Node|null $type the raw class constant type
     */
    private function classConstantType(?Node $type): Identifier|Name|ComplexType|null
    {
        if ($type instanceof Identifier || $type instanceof Name || $type instanceof ComplexType) {
            return $type;
        }

        return null;
    }

    /**
     * Changes the `@var` tag type on one class constant statement.
     *
     * @param ClassConst  $classConst the class constant statement
     * @param string|null $docType    the replacement PHPDoc type
     */
    private function changeVarTagType(ClassConst $classConst, ?string $docType): void
    {
        if (null === $docType) {
            return;
        }

        $docComment = $classConst->getDocComment();

        if (null === $docComment) {
            return;
        }

        $updatedText = preg_replace_callback(
            pattern: '/(@var\b\s+)(?:(?!\R\s*\*\s*@).)*?((?:\s+(?!\R)|\R\s*\*\/))/s',
            callback: static fn (array $matches): string => $matches[1].$docType.$matches[2],
            subject: $docComment->getText(),
        ) ?? $docComment->getText();

        if ($updatedText === $docComment->getText()) {
            return;
        }

        $classConst->setDocComment(new Doc($updatedText, $docComment->getStartLine(), $docComment->getStartFilePos()));
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
     * @return list<T>
     */
    private function cloneNodes(array $nodes): array
    {
        return array_values(array_map(static fn (Node $node): Node => clone $node, $nodes));
    }
}
