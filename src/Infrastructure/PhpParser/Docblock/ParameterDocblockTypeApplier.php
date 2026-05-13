<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRetype\Infrastructure\PhpParser\Docblock;

use PhpNoobs\PhpRetype\Domain\Retype\Operation\RetypeOperation;
use PhpNoobs\PhpRetype\Domain\Retype\Operation\RetypeOperationRole;
use PhpNoobs\PhpRetype\Domain\Retype\Target\RetypeTargetKind;
use PhpNoobs\PhpRetype\Infrastructure\PhpParser\Application\RetypeApplicationContext;
use PhpNoobs\PhpRetype\Infrastructure\PhpParser\Application\RetypeMetadataApplierInterface;
use PhpParser\Comment\Doc;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;

/**
 * Rewrites supported `@param` docblock types for matched parameter declarations.
 */
final readonly class ParameterDocblockTypeApplier implements RetypeMetadataApplierInterface
{
    /**
     * Indicates whether this applier supports the retype operation.
     *
     * @param RetypeOperation $operation the retype operation to inspect
     */
    public function supports(RetypeOperation $operation): bool
    {
        return (
            RetypeTargetKind::METHOD_PARAMETER === $operation->targetKind
            || RetypeTargetKind::FUNCTION_PARAMETER === $operation->targetKind
            || RetypeTargetKind::CLOSURE_PARAMETER === $operation->targetKind
            || RetypeTargetKind::ARROW_FUNCTION_PARAMETER === $operation->targetKind
        )
            && RetypeOperationRole::DECLARATION === $operation->role
            && $operation->node instanceof Param
            && null !== $operation->docType;
    }

    /**
     * Applies parameter docblock type changes for one retype operation.
     *
     * @param RetypeOperation          $operation the retype operation to apply
     * @param RetypeApplicationContext $context   the retype application context
     */
    public function apply(RetypeOperation $operation, RetypeApplicationContext $context): void
    {
        if (!$operation->node instanceof Param || null === $operation->docType) {
            return;
        }

        $functionLike = $this->functionLikeParent($operation->node);

        if (null === $functionLike) {
            return;
        }

        $docComment = $functionLike->getDocComment();

        if (null === $docComment) {
            return;
        }

        $parameterName = $this->parameterName($operation->node);

        if (null === $parameterName) {
            return;
        }

        $updatedText = $this->changeParamTagType(
            text: $docComment->getText(),
            parameterName: $parameterName,
            docType: $operation->docType,
        );

        if ($updatedText === $docComment->getText()) {
            return;
        }

        $functionLike->setDocComment(new Doc($updatedText, $docComment->getStartLine(), $docComment->getStartFilePos()));
    }

    /**
     * Returns the function-like parent for one parameter declaration.
     *
     * @param Param $parameter the parameter declaration node
     */
    private function functionLikeParent(Param $parameter): ClassMethod|Function_|Closure|ArrowFunction|null
    {
        $parent = $parameter->getAttribute('parent');

        if ($parent instanceof ClassMethod || $parent instanceof Function_ || $parent instanceof Closure || $parent instanceof ArrowFunction) {
            return $parent;
        }

        return null;
    }

    /**
     * Returns the parameter name without "$".
     *
     * @param Param $parameter the parameter declaration node
     */
    private function parameterName(Param $parameter): ?string
    {
        if (!$parameter->var instanceof Variable || !is_string($parameter->var->name)) {
            return null;
        }

        return $parameter->var->name;
    }

    /**
     * Changes one parameter type inside supported `@param` tags.
     *
     * @param string $text          the docblock text
     * @param string $parameterName the parameter name without "$"
     * @param string $docType       the replacement PHPDoc type
     */
    private function changeParamTagType(string $text, string $parameterName, string $docType): string
    {
        $quotedParameterName = preg_quote($parameterName, '/');

        return preg_replace_callback(
            pattern: '/(@param\b\s+)(?:(?!\R\s*\*\s*@).)*?(\s+\$'.$quotedParameterName.'\b)/s',
            callback: static fn (array $matches): string => $matches[1].$docType.$matches[2],
            subject: $text,
        ) ?? $text;
    }
}
