# Supported Retype Matrix

Navigation: [Documentation](README.md) | [Previous: Testing And Maintenance](06-testing-and-maintenance.md)

This page lists supported type-change operations.

## Matrix

| Target | Planning | Native Type Mutation | PHPDoc Mutation | Status |
| --- | --- | --- | --- | --- |
| Method parameter | `MemberGraphSourceNodeLocator::parameter(...)` | `Param::$type` | Direct parent `@param` | Supported |
| Function parameter | `MemberGraphSourceNodeLocator::parameter(...)` | `Param::$type` | Direct parent `@param` | Supported |
| Method return | `MemberGraphSourceNodeLocator::method(...)` | `ClassMethod::$returnType` | Direct method `@return` | Supported |
| Function return | `MemberGraphSourceNodeLocator::function(...)` | `Function_::$returnType` | Direct function `@return` | Supported |
| Property | `MemberGraphSourceNodeLocator::propertyDeclarationContext(...)` | `Property::$type` | Direct `@var` owner | Supported |
| Promoted property | `MemberGraphSourceNodeLocator::propertyDeclarationContext(...)` | `Param::$type` | Direct `@var` owner | Supported |
| Class constant | `MemberGraphSourceNodeLocator::classConstant(...)` | `ClassConst::$type` | Direct `@var` owner | Supported |
| Enum backing type | `MemberGraphSourceNodeLocator::owner(...)` | `Enum_::$scalarType` | Not applicable | Supported |
| Namespace/global constant | Not applicable | Not supported by PHP native syntax | Not applicable | Not supported |
| Closure parameter | TODO | TODO | TODO | TODO |
| Closure return | TODO | TODO | TODO | TODO |
| Arrow function parameter | TODO | TODO | TODO | TODO |
| Arrow function return | TODO | TODO | TODO | TODO |

## Supported Native Type Input

Public requests accept:

```text
Identifier|Name|NullableType|UnionType|IntersectionType|null
```

`null` removes the native type.

Enum backing type requests accept:

```text
Identifier
```

The identifier must be `int` or `string`.

## Supported PHPDoc Type Input

Public requests accept:

```text
string|null
```

`null` leaves the supported PHPDoc tag unchanged.

The caller is responsible for providing the PHPDoc representation explicitly. `PhpRetype` does not infer PHPDoc syntax from native PHP type syntax.

## Parameter Validation

For method and function parameters:

- `void` is rejected;
- `never` is rejected;
- blank PHPDoc type strings are rejected;
- parameter indexes must be zero or positive.

For properties:

- `void` is rejected;
- `never` is rejected;
- blank PHPDoc type strings are rejected;
- property names must be short identifiers.

For class constants:

- `void` is rejected;
- `never` is rejected;
- blank PHPDoc type strings are rejected;
- constant names must be short identifiers.

For enum backing types:

- only `int` and `string` are accepted.

For method and function returns:

- standalone `void` is accepted;
- standalone `never` is accepted;
- nullable `void`, nullable `never`, and nullable `mixed` are rejected;
- unions containing `void` or `never` are rejected;
- blank PHPDoc type strings are rejected.

Navigation: [Documentation](README.md) | [Previous: Testing And Maintenance](06-testing-and-maintenance.md)
