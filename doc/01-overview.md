# Overview

Navigation: [Documentation](README.md) | [Previous: Documentation](README.md) | [Next: Public Usage](02-public-usage.md)

`PhpRetype` is a specialized type refactoring library, not a general refactoring framework.

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

## Status

The package provides:

- `PhpRetype` public facade;
- `fromDirectory()` and `fromBuild()` construction paths;
- plan/apply APIs for property, method parameter, function parameter, method return, and function return type changes;
- transaction-neutral step execution for external orchestrators;
- standalone transactions with local rollback and source-registry save helpers;
- domain DTOs for plans, operations, results, step contexts, step results, and diagnostics;
- contracts for planning and applying retype plans;
- `member-graph` planners that convert parameter declaration matches into retype operations;
- PHPParser appliers that mutate matched property, parameter, and function declaration nodes;
- direct PHPDoc metadata updates for `@var`, `@param`, and `@return` tags;
- basic native parameter type validation.

Standalone transactions expose save helpers through the source registry from the final `member-graph` build.

## Graph Freshness

A type change can alter later member relationships and expression resolution.

Examples include:

- resolved method call targets;
- property fetch targets;
- inferred return types;
- template substitutions;
- structured PHPDoc types;
- impact query results.

For multi-step workflows, the graph must be considered stale after a successful type mutation until it is rebuilt from mutated virtual files. The step API performs this rebuild for orchestrators after each applied operation.

Navigation: [Documentation](README.md) | [Previous: Documentation](README.md) | [Next: Public Usage](02-public-usage.md)
