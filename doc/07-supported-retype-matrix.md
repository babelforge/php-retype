# Supported Retype Matrix

Navigation: [Documentation](README.md) | [Previous: Testing And Maintenance](06-testing-and-maintenance.md)

This page tracks which type-change operations are currently supported.

## Matrix

| Target | Planning | Native Type Mutation | PHPDoc Mutation | Status |
| --- | --- | --- | --- | --- |
| Method parameter | `MemberGraphSourceNodeLocator::parameter(...)` | `Param::$type` | Direct parent `@param` | Supported |
| Function parameter | Not implemented | Not implemented | Not implemented | Planned |
| Method return | Not implemented | Not implemented | Not implemented | Planned |
| Function return | Not implemented | Not implemented | Not implemented | Planned |
| Property | Not implemented | Not implemented | Not implemented | Planned |
| Promoted property | Not implemented | Not implemented | Not implemented | Planned |

## Supported Native Type Input

Current public requests accept:

```text
Identifier|Name|NullableType|UnionType|IntersectionType|null
```

`null` removes the native type.

## Supported PHPDoc Type Input

Current public requests accept:

```text
string|null
```

`null` leaves the supported PHPDoc tag unchanged.

The caller is responsible for providing the PHPDoc representation explicitly. `PhpRetype` does not infer PHPDoc syntax from native PHP type syntax.

## Current Parameter Validation

For method parameters:

- `void` is rejected;
- `never` is rejected;
- blank PHPDoc type strings are rejected;
- parameter indexes must be zero or positive.

Navigation: [Documentation](README.md) | [Previous: Testing And Maintenance](06-testing-and-maintenance.md)
