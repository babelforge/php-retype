# AST Application

Navigation: [Documentation](README.md) | [Previous: Retype Planning](04-retype-planning.md) | [Next: Testing And Maintenance](06-testing-and-maintenance.md)

AST application mutates virtual PHP files in memory from a `RetypePlan`.

It does not write physical files directly. Future save helpers should delegate physical writing to `php-source-registry`.

The applier must not discover retype targets. It only mutates nodes already present in the retype plan.

## Input

The applier receives:

- a `RetypePlan`;
- the `MemberDependencyGraphBuild` containing virtual files.

Each `RetypeOperation` references:

- the virtual file;
- the PHPParser node to mutate;
- the target kind;
- the operation role;
- the native type node to write;
- the PHPDoc type string to write when relevant.

## Output

The applier returns a `RetypeResult` containing:

- the applied plan;
- the virtual files after mutation;
- application diagnostics.

## Current Implementation

Plans containing error diagnostics are not applied.

`AstRetypePlanApplier` currently supports method parameter type changes for:

- `PhpParser\Node\Param`.

The native parameter type is replaced with a clone of the caller-provided type node, or removed when `typeNode` is `null`.

After successful node mutation, each touched `VirtualPhpSourceFile` is marked as updated through `VirtualPhpSourceFile::update()`.

Unsupported target kinds or node types produce diagnostics instead of triggering fallback source inspection.

## Docblocks

Docblock mutation is implemented as metadata application after a node mutation succeeds.

Current supported parameter docblock references:

```php
@param OldType $parameterName
```

The supported `@param` tag type is rewritten only on the direct function-like parent docblock of a matched parameter declaration.

Free-text descriptions are not rewritten. The implementation does not scan unrelated files or comments.

## Physical Writing

Physical writing is not implemented in `php-retype` yet.

The future model should mirror `php-rename`:

```php
$transaction->commitAndSave();
$transaction->commitAndSaveSourceFile('/project/src/App/Mailer.php');
```

Those helpers should delegate physical writing to the source registry exposed by the final `member-graph` build.

Navigation: [Documentation](README.md) | [Previous: Retype Planning](04-retype-planning.md) | [Next: Testing And Maintenance](06-testing-and-maintenance.md)
