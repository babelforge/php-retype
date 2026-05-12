# PhpRetype

`php-noobs/php-retype` is a semantic PHP type refactoring library built on `php-noobs/member-graph` and `php-noobs/php-source-registry`.

It changes PHP type declarations from semantic graph facts instead of textual search. The package supports property, method parameter, function parameter, method return, and function return type changes, with native PHP types and PHPDoc types provided as separate explicit inputs.

## Installation

Add the VCS repositories and require the package with Composer:

```bash
composer config repositories.php-source-registry vcs https://github.com/php-noobs/php-source-registry
composer config repositories.member-graph vcs https://github.com/php-noobs/member-graph
composer config repositories.php-retype vcs https://github.com/php-noobs/php-retype
composer require php-noobs/php-retype:dev-main
```

The package requires PHP 8.4 or newer.

## Basic Usage

```php
use PhpNoobs\PhpRetype\Application\PhpRetype;
use PhpParser\Node\Name;

$retype = PhpRetype::fromDirectory(
    directories: [$projectPath . '/src'],
    cacheFilePath: $projectPath . '/var/member-graph.cache',
);

$result = $retype->changeMethodParameterType(
    className: App\Mailer::class,
    methodName: 'send',
    parameterName: 'message',
    typeNode: new Name('Message'),
    docType: 'Message',
    parameterIndex: 0,
);
```

Property type changes accept one property name or a list of grouped property names:

```php
$result = $retype->changePropertyType(
    className: App\Mailer::class,
    propertyNames: ['transport', 'backupTransport'],
    typeNode: new Name('Transport'),
    docType: 'Transport',
);
```

When only part of a grouped property declaration is targeted, the declaration is split so untargeted properties keep their original type.

`typeNode` is the PHPParser native type node to write. `docType` is the PHPDoc type string to write. Passing `null` as `typeNode` removes the native type, and passing `null` as `docType` leaves the supported PHPDoc tag unchanged.

## Transactions

Standalone callers can group several type changes in an in-memory transaction:

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

$transactionResult = $transaction->commit();
```

Transactions refresh the in-memory member graph after each applied type change and support rollback of touched virtual files.

`commit()` keeps changes in memory. Use `commitAndSave()` to write every updated source file, or `commitAndSaveSourceFile($filePath)` to write one physical file.

## Orchestrated Steps

External orchestrators can execute transaction-neutral steps:

```php
use PhpNoobs\PhpRetype\Domain\Retype\Step\RetypeStepContext;
use PhpParser\Node\Name;

$context = RetypeStepContext::fromBuild($build);

$step = $retype->executeStepFunctionReturnTypeChange(
    context: $context,
    functionName: 'App\\send_mail',
    typeNode: new Name('SendResult'),
    docType: 'SendResult',
);
```

Step execution applies one plan and returns the refreshed context for the next operation.

## Supported Operations

| Target | Native type | PHPDoc |
| --- | --- | --- |
| Method parameter | Supported | Direct `@param` |
| Function parameter | Supported | Direct `@param` |
| Method return | Supported | Direct `@return` |
| Function return | Supported | Direct `@return` |
| Property | Supported | Direct `@var` |

Promoted properties are supported through their promoted parameter node.

## Documentation

Full documentation is available in [doc/README.md](doc/README.md).

## Quality

```bash
composer qa
```
