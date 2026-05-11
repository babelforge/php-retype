# Public Usage

Navigation: [Documentation](README.md) | [Previous: Overview](01-overview.md) | [Next: Architecture](03-architecture.md)

The public API should remain small and composable.

## Create From Directories

Use this mode when `PhpRetype` should build its own `member-graph` input:

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

This is the preferred integration point for future orchestration packages such as `php-refactor`.

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

The current implementation mutates the matched `PhpParser\Node\Param` native type and the direct parent function-like `@param` tag when `docType` is provided.

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

For method parameters:

- class names must be FQCN-like;
- method names and parameter names must be short identifiers;
- parameter indexes must be zero or positive;
- native `void` and `never` are rejected because they are invalid parameter types;
- blank PHPDoc type strings are rejected when provided.

Navigation: [Documentation](README.md) | [Previous: Overview](01-overview.md) | [Next: Architecture](03-architecture.md)
