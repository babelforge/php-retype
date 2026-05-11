# Testing And Maintenance

Navigation: [Documentation](README.md) | [Previous: AST Application](05-ast-application.md) | [Next: Supported Retype Matrix](07-supported-retype-matrix.md)

Tests should stay focused on the package boundary: planning from `member-graph` facts and applying explicit AST mutations.

## Current Test Coverage

The integration tests cover:

- method parameter native type changes;
- function parameter native type changes;
- method return native type changes;
- function return native type changes;
- direct `@param` type changes;
- direct `@return` type changes;
- native type removal;
- invalid `void` parameter type rejection;
- invalid nullable and union return type rejection.

## Quality Commands

Run the full quality suite with:

```bash
composer qa
```

Individual commands:

```bash
composer cs
composer analyse
composer test
```

## Maintenance Rules

When adding a new type-change slice:

- add a request DTO;
- add or extend a planner contract;
- convert only exact `member-graph` source-node facts into operations;
- add a focused PHPParser node applier;
- add metadata appliers only for direct supported docblock owners;
- add integration tests that prove virtual files are marked updated;
- document the new slice in the supported matrix.

When a type-change slice needs source-node facts that `member-graph` does not expose, do not build a local semantic scanner. Create an upstream request for `member-graph` and pause.

## Graph Refresh Tests

Once transactions exist, add tests proving that later actions plan against a graph rebuilt from prior type mutations.

This protects the main semantic difference between rename and retype: changing a type can change later dependency facts.

Navigation: [Documentation](README.md) | [Previous: AST Application](05-ast-application.md) | [Next: Supported Retype Matrix](07-supported-retype-matrix.md)
