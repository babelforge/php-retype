# Overview

Navigation: [Documentation](README.md) | [Previous: Documentation](README.md) | [Next: Public Usage](02-public-usage.md)

`PhpRetype` is designed as a small specialized library, not as a general refactoring framework.

Its responsibility is to change PHP type contracts safely by combining:

- semantic source-node facts from `member-graph`;
- explicit type-change requests;
- AST-level mutations on virtual PHP files;
- optional direct PHPDoc metadata updates.

## Package Boundary

`member-graph` owns read-side analysis:

- declarations;
- usages;
- source-node lookup;
- type and PHPDoc facts;
- impact queries;
- dependency relationships.

`PhpRetype` owns write-side preparation:

- type-change requests;
- retype planning;
- diagnostics;
- AST type operations;
- applying operations to loaded virtual files.

Physical file writing is delegated to `php-source-registry` through the source registry exposed by `member-graph`.

## Type Contract Rule

Every public retype request keeps native PHP type syntax and PHPDoc type syntax separate.

The caller provides:

- `typeNode`: the PHPParser native type node to write, or `null` to remove the native type;
- `docType`: the PHPDoc type string to write, or `null` to leave PHPDoc unchanged.

`PhpRetype` does not infer one representation from the other in the current implementation.

## Current Status

The current implementation provides:

- `PhpRetype` public facade;
- `fromDirectory()` and `fromBuild()` construction paths;
- plan/apply APIs for method parameter type changes;
- domain DTOs for plans, operations, results, and diagnostics;
- contracts for planning and applying retype plans;
- a `member-graph` planner that converts parameter declaration matches into retype operations;
- PHPParser appliers that mutate matched parameter declaration nodes and direct `@param` tags;
- basic native parameter type validation.

The current implementation does not yet support return types, function parameters, properties, promoted properties, transactions, or physical save helpers.

## Graph Freshness

A type change can alter future member relationships and expression resolution.

Examples include:

- resolved method call targets;
- property fetch targets;
- inferred return types;
- template substitutions;
- structured PHPDoc types;
- impact query results.

For multi-step workflows, the graph must be considered stale after a successful type mutation until it is rebuilt from mutated virtual files.

Navigation: [Documentation](README.md) | [Previous: Documentation](README.md) | [Next: Public Usage](02-public-usage.md)
