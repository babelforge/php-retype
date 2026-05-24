# PhpRetype Documentation

Navigation: [Next: Overview](01-overview.md)

This documentation describes the `PhpRetype` component, its public API, and its implementation boundaries.

`PhpRetype` is a PHP refactoring library focused on safe type changes. It consumes `babelforge/member-graph` for semantic source-node facts and uses `babelforge/php-source-registry` virtual files for AST mutation and physical writing delegation.

The package supports planning, applying, transaction-neutral step execution, and standalone in-memory transactions for declaration type changes, including properties, parameters, returns, class constants, enum backing types, closures, and arrow functions. It keeps native PHP type nodes and PHPDoc type strings as two explicit caller-provided values.

## Pages

1. [Overview](01-overview.md)
2. [Public Usage](02-public-usage.md)
3. [Architecture](03-architecture.md)
4. [Retype Planning](04-retype-planning.md)
5. [AST Application](05-ast-application.md)
6. [Testing And Maintenance](06-testing-and-maintenance.md)
7. [Supported Retype Matrix](07-supported-retype-matrix.md)
8. [Nested Callable Retype](08-nested-callable-retype.md)

## External Dependencies

`PhpRetype` consumes `babelforge/member-graph` to locate semantic source nodes.

`member-graph` depends on `babelforge/php-source-registry`, which provides virtual PHP source files and PHPParser AST access. Physical file writing remains delegated to the source registry exposed by the `member-graph` build.

`PhpRetype` uses upstream semantic facts to decide which declarations can be changed safely.

## Current Layout

The general rule is:

- `Domain/` contains retype requests, plans, operations, results, step contexts, step results, transaction results, diagnostics, target kinds, operation roles, and validation.
- `Application/` contains the public facade, step executor, standalone transaction wrapper, and contracts for use-case services.
- `Infrastructure/MemberGraph/` adapts `member-graph` source-node facts into retype plans.
- `Infrastructure/PhpParser/` applies retype plans to PHPParser AST nodes stored in virtual files.

Navigation: [Next: Overview](01-overview.md)
