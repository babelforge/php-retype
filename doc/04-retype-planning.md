# Retype Planning

Navigation: [Documentation](README.md) | [Previous: Architecture](03-architecture.md) | [Next: AST Application](05-ast-application.md)

Retype planning answers one question:

```text
Which exact AST nodes may be changed for this type change?
```

Planning does not mutate virtual files.

## Source Of Truth

`member-graph` is the only source of truth for deciding which source nodes belong to a retype operation.

`PhpRetype` does not:

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

For function parameter type changes, planning starts from the same source-node locator with an empty owner and a fully-qualified function name:

```php
use PhpNoobs\MemberGraph\Application\Source\Node\MemberGraphSourceNodeLocator;

$matches = MemberGraphSourceNodeLocator::fromBuild($build)
    ->parameter('', 'App\\send_mail', 'message', 0);
```

The returned function parameter declaration matches are converted to retype operations.

For method return type changes, planning starts from:

```php
use PhpNoobs\MemberGraph\Application\Source\Node\MemberGraphSourceNodeLocator;

$matches = MemberGraphSourceNodeLocator::fromBuild($build)
    ->method('App\\Mailer', 'send');
```

Only `MEMBER_DECLARATION` matches backed by `PhpParser\Node\Stmt\ClassMethod` nodes are converted to retype operations.

For function return type changes, planning starts from:

```php
use PhpNoobs\MemberGraph\Application\Source\Node\MemberGraphSourceNodeLocator;

$matches = MemberGraphSourceNodeLocator::fromBuild($build)
    ->function('App\\send_mail');
```

Only `MEMBER_DECLARATION` matches backed by `PhpParser\Node\Stmt\Function_` nodes are converted to retype operations.

For property type changes, planning starts from:

```php
use PhpNoobs\MemberGraph\Application\Source\Node\MemberGraphSourceNodeLocator;

$context = MemberGraphSourceNodeLocator::fromBuild($build)
    ->propertyDeclarationContext('App\\Mailer', ['transport', 'backupTransport']);
```

The returned property declaration context is converted to one or more retype operations.

Grouped property declarations produce one operation per parent `Property` statement. Promoted properties produce one operation per promoted `Param`.

## Parameter Scope

Parameter type changes mutate parameter declarations.

Property type changes mutate property declarations. Grouped property declarations are split when only part of the group is targeted.

It deliberately ignores:

- named argument usages;
- local variable usages;
- unrelated parameters with the same name;
- docblocks not attached to the direct function-like parent.

Those nodes are relevant to renaming, but they are not type declarations.

## Diagnostics

The planner reports diagnostics instead of silently guessing.

Examples:

- target parameter or function not found;
- target property not found;
- source node cannot be located;
- source node role is unsupported;
- unsupported declaration shapes.

Input validation happens before planning and throws `InvalidArgumentException` for invalid public inputs.

## Graph Freshness In Multi-Step Workflows

Planning is based on the current `MemberDependencyGraphBuild`.

After a successful type mutation, that build may no longer describe the current code accurately. Later type-change planning in the same workflow uses a fresh in-memory build from mutated virtual files.

This matters because changing type declarations or PHPDoc can change downstream member relationships and expression resolution.

Navigation: [Documentation](README.md) | [Previous: Architecture](03-architecture.md) | [Next: AST Application](05-ast-application.md)
