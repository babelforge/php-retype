# Testing And Maintenance

Navigation: [Documentation](README.md) | [Previous: AST Application](05-ast-application.md) | [Next: Supported Retype Matrix](07-supported-retype-matrix.md)

Tests focus on the package boundary: planning from `member-graph` facts and applying explicit AST mutations.

## Test Coverage

The integration tests cover:

- method parameter native type changes;
- function parameter native type changes;
- method return native type changes;
- function return native type changes;
- grouped property native type changes;
- promoted property native type changes;
- grouped property declaration splitting;
- class constant native type and direct `@var` changes;
- grouped class constant declaration splitting;
- enum backing type changes;
- closure parameter and return type changes inside methods;
- arrow-function parameter and return type changes inside functions;
- file-level nested callable type changes without exposing virtual files;
- missing nested callable index diagnostics;
- transaction-neutral step execution with refreshed member graph contexts;
- step execution blocked by plan errors;
- standalone transaction commit and rollback;
- standalone transaction save helpers;
- direct `@param` type changes;
- direct `@return` type changes;
- direct `@var` type changes;
- native type removal;
- invalid `void` parameter type rejection;
- invalid enum backing type rejection;
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

When a type-change slice needs source-node facts that `member-graph` does not expose, the implementation uses an upstream `member-graph` capability instead of a local semantic scanner.

Current backlog:

- broader edge-case coverage for deeply nested closure and arrow-function indexes;
- public orchestration exposure in downstream transaction packages.

## Graph Refresh Tests

Step API tests prove that later actions plan against a graph rebuilt from prior type mutations.

This protects the main semantic difference between rename and retype: changing a type can change later dependency facts.

Navigation: [Documentation](README.md) | [Previous: AST Application](05-ast-application.md) | [Next: Supported Retype Matrix](07-supported-retype-matrix.md)
