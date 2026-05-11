# PhpRetype Documentation

Navigation: [Next: Overview](01-overview.md)

This documentation describes the `PhpRetype` component, how to use it, how it is currently implemented, and which boundaries should guide future changes.

`PhpRetype` is a PHP refactoring library focused on safe type changes. It consumes `php-noobs/member-graph` for semantic source-node facts and uses `php-noobs/php-source-registry` virtual files for AST mutation and physical writing delegation.

The package currently supports planning and applying method parameter type changes. It keeps native PHP type nodes and PHPDoc type strings as two explicit caller-provided values.

## Pages

1. [Overview](01-overview.md)
2. [Public Usage](02-public-usage.md)
3. [Architecture](03-architecture.md)
4. [Retype Planning](04-retype-planning.md)
5. [AST Application](05-ast-application.md)
6. [Testing And Maintenance](06-testing-and-maintenance.md)
7. [Supported Retype Matrix](07-supported-retype-matrix.md)

## External Dependencies

`PhpRetype` consumes `php-noobs/member-graph` to locate semantic source nodes.

`member-graph` depends on `php-noobs/php-source-registry`, which provides virtual PHP source files and PHPParser AST access. Physical file writing remains delegated to the source registry exposed by the `member-graph` build.

`PhpRetype` must not duplicate member graph logic. It should use upstream semantic facts to decide which declarations can be changed safely.

## Current Layout

The general rule is:

- `Domain/` contains retype requests, plans, operations, results, diagnostics, target kinds, operation roles, and validation.
- `Application/` contains the public facade and contracts for use-case services.
- `Infrastructure/MemberGraph/` adapts `member-graph` source-node facts into retype plans.
- `Infrastructure/PhpParser/` applies retype plans to PHPParser AST nodes stored in virtual files.

Navigation: [Next: Overview](01-overview.md)
