<?php

declare(strict_types=1);

namespace BabelForge\PhpRetype\Domain\Retype\Request;

use BabelForge\PhpRetype\Domain\Retype\Validation\RetypeInputValidator;
use PhpParser\Node\Identifier;

/**
 * Describes an enum backing type change intent.
 */
final readonly class EnumBackingTypeChangeRequest implements RetypeRequestInterface
{
    /**
     * Constructor.
     *
     * @param string     $enumName the enum FQCN
     * @param Identifier $typeNode the native PHP backing type node to write
     *
     * @throws \InvalidArgumentException when one retype input is invalid
     */
    public function __construct(
        public string $enumName,
        public Identifier $typeNode,
    ) {
        RetypeInputValidator::guardFqcn($enumName, 'enumName');
        RetypeInputValidator::guardEnumBackingNativeType($typeNode);
    }
}
