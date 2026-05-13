# Nested Callable Retype

Navigation: [Documentation](README.md) | [Previous: Supported Retype Matrix](07-supported-retype-matrix.md)

Nested callable retype covers closures and arrow functions.

Closures and arrow functions do not have stable symbolic names. `php-retype` identifies them through a stable public container and a deterministic index inside that container.

## Containers

Supported containers:

- method: `className` and `methodName`;
- function: `functionName`;
- file: `filePath`.

Method and function containers are resolved from `member-graph`. File containers are resolved from the current build virtual files by physical file path or virtual file path.

Callers do not pass `VirtualPhpSourceFile`.

## Index Contract

`closureIndex` and `arrowFunctionIndex` are zero-based.

The index is computed in depth-first search order inside the selected container before mutation.

For a method containing an outer closure and an inner closure, index `0` targets the outer closure and index `1` targets the inner closure.

For two sibling closures in the same method body, index `0` targets the first closure and index `1` targets the second closure.

## Parameter Targets

Closure and arrow-function parameter changes require:

- the container;
- the callable index;
- `parameterName`;
- optional `parameterIndex`;
- `typeNode`;
- optional `docType`.

Example:

```php
use PhpParser\Node\Name;

$result = $retype->changeClosureParameterTypeInMethod(
    className: App\Mailer::class,
    methodName: 'send',
    closureIndex: 0,
    parameterName: 'message',
    typeNode: new Name('Message'),
    docType: 'Message',
);
```

When `parameterIndex` is provided, both the name and index must match the selected callable parameter.

## Return Targets

Closure and arrow-function return changes require:

- the container;
- the callable index;
- `typeNode`;
- optional `docType`.

Example:

```php
use PhpParser\Node\Name;

$result = $retype->changeArrowFunctionReturnTypeInFunction(
    functionName: 'App\\map_message',
    arrowFunctionIndex: 0,
    typeNode: new Name('SendResult'),
    docType: 'SendResult',
);
```

## File Containers

File containers are useful for top-level scripts and bootstrap files:

```php
use PhpParser\Node\Name;

$result = $retype->changeClosureParameterTypeInFile(
    filePath: '/project/bootstrap.php',
    closureIndex: 0,
    parameterName: 'message',
    typeNode: new Name('Message'),
    docType: 'Message',
);
```

The file path may be the physical source path or the virtual file path from the current build.

## Diagnostics

Planning returns warning diagnostics when:

- the selected container is not found;
- the callable index does not exist in the selected container;
- the selected callable parameter is not found.

Invalid inputs such as negative indexes are rejected before planning with `InvalidArgumentException`.

## PHPDoc

`php-retype` updates direct attached `@param` and `@return` tags when PHPParser carries a doc comment on the closure or arrow-function node.

If the doc comment is attached to another AST node, `php-retype` leaves it unchanged.

Navigation: [Documentation](README.md) | [Previous: Supported Retype Matrix](07-supported-retype-matrix.md)
