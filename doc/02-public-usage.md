# Public Usage

Navigation: [Documentation](README.md) | [Previous: Overview](01-overview.md) | [Next: Architecture](03-architecture.md)

The public API is small and composable.

## Create From Directories

Use this mode when `PhpRetype` builds its own `member-graph` input:

```php
use PhpNoobs\PhpRetype\Application\PhpRetype;

$retype = PhpRetype::fromDirectory(
    directories: [$projectPath . '/src'],
    cacheFilePath: $projectPath . '/var/member-graph.cache',
);
```

## Create From Existing Build

Use this mode when another tool already built a `member-graph` result:

```php
use PhpNoobs\PhpRetype\Application\PhpRetype;

$retype = PhpRetype::fromBuild($build);
```

This is the integration point for orchestration packages that already own a current member graph build.

## Execute Orchestrable Retype Steps

External orchestrators can use the transaction-neutral step API instead of the direct plan/apply helpers.

```php
use PhpNoobs\PhpRetype\Domain\Retype\Step\RetypeStepContext;
use PhpParser\Node\Name;

$context = RetypeStepContext::fromBuild($build);

$step = $retype->executeStepMethodParameterTypeChange(
    context: $context,
    className: App\Mailer::class,
    methodName: 'send',
    parameterName: 'message',
    typeNode: new Name('Message'),
    docType: 'Message',
    parameterIndex: 0,
);

$context = $step->context;
```

The available step methods mirror the supported direct operations:

- `executeStepMethodParameterTypeChange()`;
- `executeStepFunctionParameterTypeChange()`;
- `executeStepMethodReturnTypeChange()`;
- `executeStepFunctionReturnTypeChange()`;
- `executeStepPropertyTypeChange()`;
- `executeStepClassConstantTypeChange()`;
- `executeStepEnumBackingTypeChange()`;
- `executeStepClosureParameterTypeInMethod()`;
- `executeStepClosureReturnTypeInMethod()`;
- `executeStepArrowFunctionParameterTypeInMethod()`;
- `executeStepArrowFunctionReturnTypeInMethod()`;
- matching `InFunction` and `InFile` variants.

Each successful step applies the plan to virtual files and returns a refreshed `RetypeStepContext`.

`php-retype` rebuilds the current member graph from mutated virtual files after every applied step. This keeps later steps aligned with type replacements that change graph relationships.

The lower-level `executeStep()` method accepts a preplanned `RetypePlan` and a `RetypeStepContext`.

## Use A Retype Transaction

Standalone callers can use a local transaction wrapper when several type changes must share refreshed in-memory graph state:

```php
use PhpParser\Node\Name;

$transaction = $retype->beginTransaction();

$transaction->changeMethodParameterType(
    className: App\Mailer::class,
    methodName: 'send',
    parameterName: 'message',
    typeNode: new Name('Message'),
    docType: 'Message',
    parameterIndex: 0,
);

$transaction->changeMethodReturnType(
    className: App\Mailer::class,
    methodName: 'send',
    typeNode: new Name('SendResult'),
    docType: 'SendResult',
);

$result = $transaction->commit();
```

`PhpRetypeTransaction` is the local transaction wrapper for standalone `php-retype` usage. It uses the same step execution path as external orchestration, but adds local snapshots, local rollback, local status transitions, and aggregate transaction results.

External orchestrators call the `executeStep...TypeChange()` methods directly instead of nesting `PhpRetypeTransaction`.

`commit()` remains in-memory only.

Use `commitAndSave()` to commit and write every updated source file:

```php
$result = $transaction->commitAndSave();
```

Use `commitAndSaveSourceFile()` to commit and write one physical source file:

```php
$result = $transaction->commitAndSaveSourceFile('/project/src/App/Mailer.php');
```

Physical file writing is delegated to the source registry available through the final member graph build.

`rollback()` restores the virtual files touched by successful transaction actions.

## Plan A Property Type Change

Property type changes use the owner FQCN and one property name or a list of grouped property names:

```php
use PhpParser\Node\Name;

$plan = $retype->planPropertyTypeChange(
    className: App\Mailer::class,
    propertyNames: ['transport', 'backupTransport'],
    typeNode: new Name('Transport'),
    docType: 'Transport',
);
```

## Apply A Property Type Change

The convenience method plans and applies in one call:

```php
use PhpParser\Node\Name;

$result = $retype->changePropertyType(
    className: App\Mailer::class,
    propertyNames: ['transport', 'backupTransport'],
    typeNode: new Name('Transport'),
    docType: 'Transport',
);
```

The operation mutates the matched property native type and the direct `@var` tag when `docType` is provided.

If every property in a grouped declaration is targeted, the parent `Property::$type` is changed in place. If only a subset is targeted, the grouped declaration is split so untargeted properties keep their original native type and PHPDoc.

Promoted properties are retyped through their promoted `PhpParser\Node\Param` node.

## Plan A Class Constant Type Change

Class constant type changes use the owner FQCN and constant name:

```php
use PhpParser\Node\Identifier;

$plan = $retype->planClassConstantTypeChange(
    className: App\Config::class,
    constantName: 'DEFAULT_PORT',
    typeNode: new Identifier('int'),
    docType: 'int',
);
```

## Apply A Class Constant Type Change

The convenience method plans and applies in one call:

```php
use PhpParser\Node\Identifier;

$result = $retype->changeClassConstantType(
    className: App\Config::class,
    constantName: 'DEFAULT_PORT',
    typeNode: new Identifier('int'),
    docType: 'int',
);
```

The operation mutates the matched `PhpParser\Node\Stmt\ClassConst` native type and the direct `@var` tag when `docType` is provided.

If only part of a grouped class constant declaration is targeted, the grouped declaration is split so untargeted constants keep their original native type and PHPDoc.

## Plan An Enum Backing Type Change

Enum backing type changes use the enum FQCN:

```php
use PhpParser\Node\Identifier;

$plan = $retype->planEnumBackingTypeChange(
    enumName: App\Status::class,
    typeNode: new Identifier('int'),
);
```

## Apply An Enum Backing Type Change

The convenience method plans and applies in one call:

```php
use PhpParser\Node\Identifier;

$result = $retype->changeEnumBackingType(
    enumName: App\Status::class,
    typeNode: new Identifier('int'),
);
```

Enum backing type changes accept only `int` and `string` identifiers, matching PHP backed enum syntax.

## Plan A Closure Or Arrow-Function Type Change

Closures and arrow functions are selected by a public container and a zero-based index inside that container.

Supported containers:

- method: `className` and `methodName`;
- function: `functionName`;
- file: `filePath`.

Supported nested callable kinds:

- closure;
- arrow function.

Supported targets:

- parameter type;
- return type.

Indexes are computed in deterministic DFS order inside the selected container before mutation.

Example for a closure return inside a method:

```php
use PhpParser\Node\Name;

$plan = $retype->planClosureReturnTypeInMethod(
    className: App\Mailer::class,
    methodName: 'send',
    closureIndex: 0,
    typeNode: new Name('SendResult'),
    docType: 'SendResult',
);
```

Example for an arrow-function parameter inside a function:

```php
use PhpParser\Node\Name;

$result = $retype->changeArrowFunctionParameterTypeInFunction(
    functionName: 'App\\map_message',
    arrowFunctionIndex: 0,
    parameterName: 'message',
    typeNode: new Name('Message'),
    docType: 'Message',
);
```

Example for a closure parameter inside a file:

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

The same naming pattern exists for direct `plan...`, direct `change...`, and orchestrable `executeStep...` methods.

`php-retype` finds the virtual file from the selected container. Callers do not need to know `VirtualPhpSourceFile`.

See [Nested Callable Retype](08-nested-callable-retype.md) for the complete container and index contract.

## Plan A Method Parameter Type Change

Planning produces operations and diagnostics without mutating virtual files:

```php
use PhpParser\Node\Name;

$plan = $retype->planMethodParameterTypeChange(
    className: App\Mailer::class,
    methodName: 'send',
    parameterName: 'message',
    typeNode: new Name('Message'),
    docType: 'Message',
    parameterIndex: 0,
);
```

The native type node and PHPDoc type string are intentionally separate inputs.

## Apply A Method Parameter Type Change

The convenience method plans and applies in one call:

```php
use PhpParser\Node\Name;

$result = $retype->changeMethodParameterType(
    className: App\Mailer::class,
    methodName: 'send',
    parameterName: 'message',
    typeNode: new Name('Message'),
    docType: 'Message',
    parameterIndex: 0,
);
```

The operation mutates the matched `PhpParser\Node\Param` native type and the direct parent function-like `@param` tag when `docType` is provided.

## Plan A Function Parameter Type Change

Function parameters use the fully-qualified function name:

```php
use PhpParser\Node\Name;

$plan = $retype->planFunctionParameterTypeChange(
    functionName: 'App\\send_mail',
    parameterName: 'message',
    typeNode: new Name('Message'),
    docType: 'Message',
    parameterIndex: 0,
);
```

## Apply A Function Parameter Type Change

The convenience method plans and applies in one call:

```php
use PhpParser\Node\Name;

$result = $retype->changeFunctionParameterType(
    functionName: 'App\\send_mail',
    parameterName: 'message',
    typeNode: new Name('Message'),
    docType: 'Message',
    parameterIndex: 0,
);
```

The operation mutates the matched function `PhpParser\Node\Param` native type and the direct function `@param` tag when `docType` is provided.

## Plan A Method Return Type Change

Method return changes use the owner FQCN and method name:

```php
use PhpParser\Node\Name;

$plan = $retype->planMethodReturnTypeChange(
    className: App\Mailer::class,
    methodName: 'send',
    typeNode: new Name('SendResult'),
    docType: 'SendResult',
);
```

## Apply A Method Return Type Change

The convenience method plans and applies in one call:

```php
use PhpParser\Node\Name;

$result = $retype->changeMethodReturnType(
    className: App\Mailer::class,
    methodName: 'send',
    typeNode: new Name('SendResult'),
    docType: 'SendResult',
);
```

The operation mutates the matched `PhpParser\Node\Stmt\ClassMethod` return type and the direct method `@return` tag when `docType` is provided.

## Plan A Function Return Type Change

Function return changes also use the fully-qualified function name:

```php
use PhpParser\Node\Name;

$plan = $retype->planFunctionReturnTypeChange(
    functionName: 'App\\send_mail',
    typeNode: new Name('SendResult'),
    docType: 'SendResult',
);
```

## Apply A Function Return Type Change

The convenience method plans and applies in one call:

```php
use PhpParser\Node\Name;

$result = $retype->changeFunctionReturnType(
    functionName: 'App\\send_mail',
    typeNode: new Name('SendResult'),
    docType: 'SendResult',
);
```

The operation mutates the matched `PhpParser\Node\Stmt\Function_` return type and the direct function `@return` tag when `docType` is provided.

## Remove A Native Type

Pass `null` as `typeNode` to remove the native type while optionally keeping or changing PHPDoc:

```php
$result = $retype->changeMethodParameterType(
    className: App\Mailer::class,
    methodName: 'send',
    parameterName: 'message',
    typeNode: null,
    docType: 'array{subject: string}',
    parameterIndex: 0,
);
```

## Validation

Requests validate basic input before `member-graph` lookup.

For method and function parameters:

- class names must be FQCN-like;
- method names, parameter names, and property names must be short identifiers;
- class constant names must be short identifiers;
- parameter indexes must be zero or positive;
- native `void` and `never` are rejected because they are invalid parameter and property types;
- native `void` and `never` are accepted as standalone return types;
- enum backing types must be `int` or `string`;
- closure and arrow-function indexes must be zero or positive;
- nullable `void`, nullable `never`, nullable `mixed`, and unions containing `void` or `never` are rejected for return types;
- blank PHPDoc type strings are rejected when provided.

Navigation: [Documentation](README.md) | [Previous: Overview](01-overview.md) | [Next: Architecture](03-architecture.md)
