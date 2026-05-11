# Retype Planning

Navigation: [Documentation](README.md) | [Previous: Architecture](03-architecture.md) | [Next: AST Application](05-ast-application.md)

Retype planning answers one question:

```text
Which exact AST nodes may be changed for this type change?
```

Planning must not mutate virtual files.

## Source Of Truth

`member-graph` is the only source of truth for deciding which source nodes belong to a retype operation.

`PhpRetype` must not:

- perform textual search;
- traverse unrelated AST nodes to discover extra candidates;
- rebuild inheritance, trait, interface, or consumer scopes;
- apply fallback replacements on nodes that only look similar.

For method parameter type changes, planning starts from:

```php
use PhpNoobs\MemberGraph\Application\Source\Node\MemberGraphSourceNodeLocator;

$matches = MemberGraphSourceNodeLocator::fromBuild($build)
    ->parameter('App\\Mailer', 'send', 'message', 0);
```

The returned parameter declaration matches are converted to retype operations.

## Method Parameter Scope

The current slice only mutates parameter declarations.

It deliberately ignores:

- named argument usages;
- local variable usages;
- unrelated parameters with the same name;
- docblocks not attached to the direct function-like parent.

Those nodes are relevant to renaming, but they are not type declarations.

## Diagnostics

The planner must report diagnostics instead of silently guessing.

Examples:

- target parameter not found;
- source node cannot be located;
- source node role is unsupported;
- later slices may report unsupported declaration shapes.

Input validation happens before planning and throws `InvalidArgumentException` for invalid public inputs.

## Graph Freshness In Multi-Step Workflows

Planning is based on the current `MemberDependencyGraphBuild`.

After a successful type mutation, that build may no longer describe the current code accurately. Later type-change planning in the same workflow should use a fresh in-memory build from mutated virtual files.

This matters because changing type declarations or PHPDoc can change downstream member relationships and expression resolution.

Navigation: [Documentation](README.md) | [Previous: Architecture](03-architecture.md) | [Next: AST Application](05-ast-application.md)
