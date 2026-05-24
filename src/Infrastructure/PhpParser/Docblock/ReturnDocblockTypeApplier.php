<?php

declare(strict_types=1);

namespace BabelForge\PhpRetype\Infrastructure\PhpParser\Docblock;

use BabelForge\PhpRetype\Domain\Retype\Operation\RetypeOperation;
use BabelForge\PhpRetype\Domain\Retype\Operation\RetypeOperationRole;
use BabelForge\PhpRetype\Domain\Retype\Target\RetypeTargetKind;
use BabelForge\PhpRetype\Infrastructure\PhpParser\Application\RetypeApplicationContext;
use BabelForge\PhpRetype\Infrastructure\PhpParser\Application\RetypeMetadataApplierInterface;
use PhpParser\Comment\Doc;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;

/**
 * Rewrites supported `@return` docblock types for matched function-like declarations.
 */
final readonly class ReturnDocblockTypeApplier implements RetypeMetadataApplierInterface
{
    /**
     * Indicates whether this applier supports the retype operation.
     *
     * @param RetypeOperation $operation the retype operation to inspect
     */
    public function supports(RetypeOperation $operation): bool
    {
        return (
            RetypeTargetKind::FUNCTION_RETURN === $operation->targetKind
            || RetypeTargetKind::METHOD_RETURN === $operation->targetKind
            || RetypeTargetKind::CLOSURE_RETURN === $operation->targetKind
            || RetypeTargetKind::ARROW_FUNCTION_RETURN === $operation->targetKind
        )
            && RetypeOperationRole::DECLARATION === $operation->role
            && (
                $operation->node instanceof Function_
                || $operation->node instanceof ClassMethod
                || $operation->node instanceof Closure
                || $operation->node instanceof ArrowFunction
            )
            && null !== $operation->docType;
    }

    /**
     * Applies return docblock type changes for one retype operation.
     *
     * @param RetypeOperation          $operation the retype operation to apply
     * @param RetypeApplicationContext $context   the retype application context
     */
    public function apply(RetypeOperation $operation, RetypeApplicationContext $context): void
    {
        if (
            (
                !$operation->node instanceof Function_
                && !$operation->node instanceof ClassMethod
                && !$operation->node instanceof Closure
                && !$operation->node instanceof ArrowFunction
            )
            || null === $operation->docType
        ) {
            return;
        }

        $docComment = $operation->node->getDocComment();

        if (null === $docComment) {
            return;
        }

        $updatedText = $this->changeReturnTagType($docComment->getText(), $operation->docType);

        if ($updatedText === $docComment->getText()) {
            return;
        }

        $operation->node->setDocComment(new Doc($updatedText, $docComment->getStartLine(), $docComment->getStartFilePos()));
    }

    /**
     * Changes the type inside supported `@return` tags.
     *
     * @param string $text    the docblock text
     * @param string $docType the replacement PHPDoc type
     */
    private function changeReturnTagType(string $text, string $docType): string
    {
        return preg_replace_callback(
            pattern: '/(@return\b\s+)(?:(?!\R\s*\*\s*@).)*?((?:\s+(?!\R)|\R\s*\*\/))/s',
            callback: static fn (array $matches): string => $matches[1].$docType.$matches[2],
            subject: $text,
        ) ?? $text;
    }
}
