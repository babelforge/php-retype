<?php

declare(strict_types=1);

namespace BabelForge\PhpRetype\Infrastructure\PhpParser\Docblock;

use BabelForge\PhpRetype\Domain\Retype\Operation\RetypeOperation;
use BabelForge\PhpRetype\Domain\Retype\Operation\RetypeOperationRole;
use BabelForge\PhpRetype\Domain\Retype\Target\RetypeTargetKind;
use BabelForge\PhpRetype\Infrastructure\PhpParser\Application\RetypeApplicationContext;
use BabelForge\PhpRetype\Infrastructure\PhpParser\Application\RetypeMetadataApplierInterface;
use PhpParser\Comment\Doc;
use PhpParser\Node;

/**
 * Rewrites supported `@var` docblock types for matched property declarations.
 */
final readonly class VarDocblockTypeApplier implements RetypeMetadataApplierInterface
{
    /**
     * Indicates whether this applier supports the retype operation.
     *
     * @param RetypeOperation $operation the retype operation to inspect
     */
    public function supports(RetypeOperation $operation): bool
    {
        return RetypeTargetKind::PROPERTY === $operation->targetKind
            && RetypeOperationRole::DECLARATION === $operation->role
            && null !== $operation->docType
            && null !== $operation->propertyContext?->phpDocOwner;
    }

    /**
     * Applies property docblock type changes for one retype operation.
     *
     * @param RetypeOperation          $operation the retype operation to apply
     * @param RetypeApplicationContext $context   the retype application context
     */
    public function apply(RetypeOperation $operation, RetypeApplicationContext $context): void
    {
        $phpDocOwner = $operation->propertyContext?->phpDocOwner;

        if (!$phpDocOwner instanceof Node || null === $operation->docType) {
            return;
        }

        $docComment = $phpDocOwner->getDocComment();

        if (null === $docComment) {
            return;
        }

        $updatedText = $this->changeVarTagType($docComment->getText(), $operation->docType);

        if ($updatedText === $docComment->getText()) {
            return;
        }

        $phpDocOwner->setDocComment(new Doc($updatedText, $docComment->getStartLine(), $docComment->getStartFilePos()));
    }

    /**
     * Changes the type inside supported `@var` tags.
     *
     * @param string $text    the docblock text
     * @param string $docType the replacement PHPDoc type
     */
    private function changeVarTagType(string $text, string $docType): string
    {
        return preg_replace_callback(
            pattern: '/(@var\b\s+)(?:(?!\R\s*\*\s*@).)*?((?:\s+(?!\R)|\R\s*\*\/))/s',
            callback: static fn (array $matches): string => $matches[1].$docType.$matches[2],
            subject: $text,
        ) ?? $text;
    }
}
