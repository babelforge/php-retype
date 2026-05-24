<?php

declare(strict_types=1);

namespace BabelForge\PhpRetype\Tests\Integration;

use BabelForge\MemberGraph\Application\Build\Factory\MemberDependencyGraphFactory;
use BabelForge\PhpRetype\Application\PhpRetype;
use BabelForge\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnostic;
use BabelForge\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnosticSeverity;
use BabelForge\PhpRetype\Domain\Retype\Step\RetypeStepContext;
use BabelForge\PhpSource\VirtualPhpSourceFileCollection;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPUnit\Framework\TestCase;

/**
 * Covers closure and arrow-function type changes against real member-graph builds.
 */
final class PhpRetypeNestedCallableTypeChangeIntegrationTest extends TestCase
{
    private string $workspace;

    /**
     * Creates a temporary integration workspace.
     */
    protected function setUp(): void
    {
        $this->workspace = sys_get_temp_dir().'/php-retype-nested-callable-'.str_replace('.', '', uniqid('', true));
        mkdir($this->workspace, 0o777, true);
    }

    /**
     * Removes the temporary integration workspace.
     */
    protected function tearDown(): void
    {
        $this->removeDirectory($this->workspace);
    }

    /**
     * Ensures a closure parameter and return type can be changed inside a method.
     */
    public function testItChangesClosureTypesInsideAMethod(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        $this->writeMailerFile($srcDirectory.'/Mailer.php');
        $this->writeMessageFile($srcDirectory.'/Message.php');
        $this->writeSendResultFile($srcDirectory.'/SendResult.php');

        $transaction = PhpRetype::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
            clearCache: true,
        )->beginTransaction();

        $transaction->changeClosureParameterTypeInMethod(
            className: 'App\\Mailer',
            methodName: 'send',
            closureIndex: 0,
            parameterName: 'message',
            typeNode: new Name('Message'),
            docType: 'Message',
        );
        $transaction->changeClosureReturnTypeInMethod(
            className: 'App\\Mailer',
            methodName: 'send',
            closureIndex: 0,
            typeNode: new Name('SendResult'),
            docType: 'SendResult',
        );

        $transactionResult = $transaction->commit();
        $printedCode = $this->printedCode($transactionResult->virtualFiles);

        self::assertCount(2, $transactionResult->actionResults);
        self::assertCount(0, $transactionResult->diagnostics);
        self::assertStringContainsString('function (\\App\\Message $message): \\App\\SendResult', $printedCode);
    }

    /**
     * Ensures an arrow-function parameter and return type can be changed inside a function.
     */
    public function testItChangesArrowFunctionTypesInsideAFunction(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        $this->writeFunctionFile($srcDirectory.'/functions.php');
        $this->writeMessageFile($srcDirectory.'/Message.php');
        $this->writeSendResultFile($srcDirectory.'/SendResult.php');

        $retype = PhpRetype::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
            clearCache: true,
        );

        $parameterResult = $retype->changeArrowFunctionParameterTypeInFunction(
            functionName: 'App\\map_message',
            arrowFunctionIndex: 0,
            parameterName: 'message',
            typeNode: new Name('Message'),
            docType: 'Message',
        );
        $returnResult = PhpRetype::fromBuild(MemberDependencyGraphFactory::fromVirtualFiles($parameterResult->virtualFiles))
            ->changeArrowFunctionReturnTypeInFunction(
                functionName: 'App\\map_message',
                arrowFunctionIndex: 0,
                typeNode: new Name('SendResult'),
                docType: 'SendResult',
            );
        $printedCode = $this->printedCode($returnResult->virtualFiles);

        self::assertCount(1, $parameterResult->plan->operations);
        self::assertCount(1, $returnResult->plan->operations);
        self::assertCount(0, $returnResult->diagnostics);
        self::assertStringContainsString('fn(\\App\\Message $message): SendResult => $message', $printedCode);
    }

    /**
     * Ensures file-level containers can be used without exposing virtual files.
     */
    public function testItChangesNestedCallableTypesInsideAFile(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';
        $filePath = $srcDirectory.'/bootstrap.php';

        mkdir($srcDirectory, 0o777, true);
        $this->writeBootstrapFile($filePath);
        $this->writeMessageFile($srcDirectory.'/Message.php');

        $build = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
            clearCache: true,
        );
        $context = RetypeStepContext::fromBuild($build);
        $step = PhpRetype::fromBuild($build)->executeStepClosureParameterTypeInFile(
            context: $context,
            filePath: $filePath,
            closureIndex: 0,
            parameterName: 'message',
            typeNode: new Name('Message'),
            docType: 'Message',
        );
        $printedCode = $this->printedCode($step->context->currentBuild->virtualFiles);

        self::assertTrue($step->applied);
        self::assertTrue($step->requiresGraphRefresh);
        self::assertTrue($step->context->currentBuild->usedInMemoryPartialRefresh());
        self::assertCount(0, $step->diagnostics);
        self::assertStringContainsString('function (\\App\\Message $message): string', $printedCode);
    }

    /**
     * Ensures a missing nested callable index produces a warning plan.
     */
    public function testItReportsMissingNestedCallableIndex(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        $this->writeMailerFile($srcDirectory.'/Mailer.php');

        $plan = PhpRetype::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
            clearCache: true,
        )->planClosureReturnTypeInMethod(
            className: 'App\\Mailer',
            methodName: 'send',
            closureIndex: 5,
            typeNode: new Identifier('string'),
            docType: 'string',
        );

        self::assertCount(0, $plan->operations);
        self::assertFalse($plan->diagnostics->hasErrors());
        self::assertCount(1, $plan->diagnostics);
    }

    /**
     * Ensures nested closure indexes follow DFS order inside the selected container.
     */
    public function testItSelectsNestedClosureByDepthFirstIndex(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        $this->writeNestedClosureFile($srcDirectory.'/Nested.php');
        $this->writeSendResultFile($srcDirectory.'/SendResult.php');

        $result = PhpRetype::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
            clearCache: true,
        )->changeClosureReturnTypeInMethod(
            className: 'App\\Nested',
            methodName: 'build',
            closureIndex: 1,
            typeNode: new Name('SendResult'),
            docType: 'SendResult',
        );
        $printedCode = $this->printedCode($result->virtualFiles);

        self::assertCount(1, $result->plan->operations);
        self::assertCount(0, $result->diagnostics);
        self::assertStringContainsString('function (): string {', $printedCode);
        self::assertStringContainsString('function (): SendResult {', $printedCode);
    }

    /**
     * Ensures sibling closure indexes select the requested closure only.
     */
    public function testItSelectsSiblingClosureByIndex(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        $this->writeSiblingClosureFile($srcDirectory.'/Sibling.php');
        $this->writeMessageFile($srcDirectory.'/Message.php');

        $result = PhpRetype::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
            clearCache: true,
        )->changeClosureParameterTypeInMethod(
            className: 'App\\Sibling',
            methodName: 'build',
            closureIndex: 1,
            parameterName: 'message',
            typeNode: new Name('Message'),
            docType: 'Message',
        );
        $printedCode = $this->printedCode($result->virtualFiles);

        self::assertCount(1, $result->plan->operations);
        self::assertCount(0, $result->diagnostics);
        self::assertStringContainsString('$first = function (string $message): string', $printedCode);
        self::assertStringContainsString('$second = function (Message $message): string', $printedCode);
    }

    /**
     * Ensures missing nested callable parameters report an actionable diagnostic.
     */
    public function testItReportsMissingNestedCallableParameter(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        $this->writeMailerFile($srcDirectory.'/Mailer.php');

        $plan = PhpRetype::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
            clearCache: true,
        )->planClosureParameterTypeInMethod(
            className: 'App\\Mailer',
            methodName: 'send',
            closureIndex: 0,
            parameterName: 'missingMessage',
            typeNode: new Identifier('string'),
            docType: 'string',
        );
        $diagnostic = $this->firstDiagnostic($plan->diagnostics);

        self::assertCount(0, $plan->operations);
        self::assertFalse($plan->diagnostics->hasErrors());
        self::assertNotNull($diagnostic);
        self::assertSame(RetypeDiagnosticSeverity::WARNING, $diagnostic->severity);
        self::assertSame('Nested callable parameter "missingMessage" was not found for the requested type change.', $diagnostic->message);
    }

    /**
     * Ensures negative nested callable indexes are rejected before planning.
     */
    public function testItRejectsNegativeNestedCallableIndexBeforePlanning(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "callableIndex" retype input must be greater than or equal to zero.');

        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        $this->writeMailerFile($srcDirectory.'/Mailer.php');

        PhpRetype::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
            clearCache: true,
        )->planClosureReturnTypeInMethod(
            className: 'App\\Mailer',
            methodName: 'send',
            closureIndex: -1,
            typeNode: new Identifier('string'),
            docType: 'string',
        );
    }

    /**
     * Writes the mailer fixture.
     *
     * @param string $filePath the file path
     */
    private function writeMailerFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            final class Mailer
            {
                public function send(): void
                {
                    $handler = /**
                     * @param string $message
                     *
                     * @return string
                     */
                    function (string $message): string {
                        return $message;
                    };
                }
            }
            PHP);
    }

    /**
     * Writes the function fixture.
     *
     * @param string $filePath the file path
     */
    private function writeFunctionFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            function map_message(): void
            {
                $mapper = fn(string $message): string => $message;
            }
            PHP);
    }

    /**
     * Writes the bootstrap fixture.
     *
     * @param string $filePath the file path
     */
    private function writeBootstrapFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            $handler = function (string $message): string {
                return $message;
            };
            PHP);
    }

    /**
     * Writes the nested closure fixture.
     *
     * @param string $filePath the file path
     */
    private function writeNestedClosureFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            final class Nested
            {
                public function build(): void
                {
                    $outer = function (): string {
                        $inner = function (): string {
                            return 'inner';
                        };

                        return $inner();
                    };
                }
            }
            PHP);
    }

    /**
     * Writes the sibling closure fixture.
     *
     * @param string $filePath the file path
     */
    private function writeSiblingClosureFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            final class Sibling
            {
                public function build(): void
                {
                    $first = function (string $message): string {
                        return $message;
                    };
                    $second = function (string $message): string {
                        return $message;
                    };
                }
            }
            PHP);
    }

    /**
     * Writes the message fixture.
     *
     * @param string $filePath the file path
     */
    private function writeMessageFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            final class Message
            {
            }
            PHP);
    }

    /**
     * Writes the send result fixture.
     *
     * @param string $filePath the file path
     */
    private function writeSendResultFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            final class SendResult
            {
            }
            PHP);
    }

    /**
     * Prints all virtual files into a single string.
     *
     * @param VirtualPhpSourceFileCollection $virtualFiles the virtual files to print
     */
    private function printedCode(VirtualPhpSourceFileCollection $virtualFiles): string
    {
        $printedCode = '';

        foreach ($virtualFiles as $virtualFile) {
            $printedCode .= $virtualFile->standardPrint($virtualFile->nodes)."\n";
        }

        return $printedCode;
    }

    /**
     * Returns the first diagnostic in a collection.
     *
     * @param iterable<RetypeDiagnostic> $diagnostics the diagnostics to inspect
     */
    private function firstDiagnostic(iterable $diagnostics): ?RetypeDiagnostic
    {
        foreach ($diagnostics as $diagnostic) {
            return $diagnostic;
        }

        return null;
    }

    /**
     * Removes a directory recursively.
     *
     * @param string $directory the directory path
     */
    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo instanceof \SplFileInfo) {
                continue;
            }

            if ($fileInfo->isDir()) {
                rmdir($fileInfo->getPathname());

                continue;
            }

            unlink($fileInfo->getPathname());
        }

        rmdir($directory);
    }
}
