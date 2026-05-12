# AST Application

Navigation: [Documentation](README.md) | [Previous: Retype Planning](04-retype-planning.md) | [Next: Testing And Maintenance](06-testing-and-maintenance.md)

AST application mutates virtual PHP files in memory from a `RetypePlan`.

It does not write physical files directly. Physical writing is delegated to `php-source-registry` through the source registry exposed by the `member-graph` build.

The applier does not discover retype targets. It only mutates nodes already present in the retype plan.

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

## Implementation

Plans containing error diagnostics are not applied.

`AstRetypePlanApplier` supports method and function parameter type changes for:

- `PhpParser\Node\Param`.

The native parameter type is replaced with a clone of the caller-provided type node, or removed when `typeNode` is `null`.

It also supports method and function return type changes for:

- `PhpParser\Node\Stmt\ClassMethod`;
- `PhpParser\Node\Stmt\Function_`.

The native function-like return type is replaced with a clone of the caller-provided type node, or removed when `typeNode` is `null`.

It supports property type changes for:

- `PhpParser\Node\Stmt\Property`;
- promoted property `PhpParser\Node\Param`.

When every property in a grouped `Property` statement is targeted, the native property type is replaced in place.

When only part of a grouped `Property` statement is targeted, the applier keeps the targeted properties on the original statement, writes the new native type there, and inserts a second statement for the remaining properties with their original native type.

After successful node mutation, each touched `VirtualPhpSourceFile` is marked as updated through `VirtualPhpSourceFile::update()`.

Unsupported target kinds or node types produce diagnostics instead of triggering fallback source inspection.

## Docblocks

Docblock mutation is implemented as metadata application after a node mutation succeeds.

Supported parameter docblock references:

```php
@param OldType $parameterName
```

The supported `@param` tag type is rewritten only on the direct method or function docblock of a matched parameter declaration.

Supported return docblock references:

```php
@return OldType
```

The supported `@return` tag type is rewritten only on the direct method or function docblock of a matched function-like declaration.

Supported property docblock references:

```php
@var OldType
```

The supported `@var` tag type is rewritten only on the direct property or promoted-property docblock owner reported by `member-graph`.

Free-text descriptions are not rewritten. The implementation does not scan unrelated files or comments.

## Physical Writing

Physical writing is performed through the source registry exposed by the final `member-graph` build.

Standalone transactions expose this through:

```php
$transaction->commitAndSave();
$transaction->commitAndSaveSourceFile('/project/src/App/Mailer.php');
```

Navigation: [Documentation](README.md) | [Previous: Retype Planning](04-retype-planning.md) | [Next: Testing And Maintenance](06-testing-and-maintenance.md)
