<?php

declare(strict_types=1);

namespace BabelForge\PhpRetype\Domain\Retype\Target;

/**
 * Identifies the semantic target kind being retyped.
 */
enum RetypeTargetKind: string
{
    case METHOD_PARAMETER = 'method_parameter';
    case FUNCTION_PARAMETER = 'function_parameter';
    case METHOD_RETURN = 'method_return';
    case FUNCTION_RETURN = 'function_return';
    case PROPERTY = 'property';
    case CLASS_CONSTANT = 'class_constant';
    case ENUM_BACKING = 'enum_backing';
    case CLOSURE_PARAMETER = 'closure_parameter';
    case CLOSURE_RETURN = 'closure_return';
    case ARROW_FUNCTION_PARAMETER = 'arrow_function_parameter';
    case ARROW_FUNCTION_RETURN = 'arrow_function_return';
}
