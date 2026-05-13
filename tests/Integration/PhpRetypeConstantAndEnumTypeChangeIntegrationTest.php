<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRetype\Tests\Integration;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphFactory;
use PhpNoobs\PhpRetype\Application\PhpRetype;
use PhpNoobs\PhpRetype\Domain\Retype\Step\RetypeStepContext;
use PhpNoobs\PhpSource\VirtualPhpSourceFileCollection;
use PhpParser\Node\Identifier;
use PHPUnit\Framework\TestCase;

/**
 * Covers class constant and enum backing type changes against real member-graph builds.
 */
final class PhpRetypeConstantAndEnumTypeChangeIntegrationTest extends TestCase
{
    private string $workspace;

    /**
     * Creates a temporary integration workspace.
     */
    protected function setUp(): void
    {
        $this->workspace = sys_get_temp_dir().'/php-retype-constant-enum-'.str_replace('.', '', uniqid('', true));
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
     * Ensures a class constant native type and PHPDoc type can be changed.
     */
    public function testItChangesClassConstantNativeTypeAndDocblockType(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        $this->writeConfigFile($srcDirectory.'/Config.php');

        $result = PhpRetype::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
            clearCache: true,
        )->changeClassConstantType(
            className: 'App\\Config',
            constantName: 'DEFAULT_PORT',
            typeNode: new Identifier('int'),
            docType: 'int',
        );
        $printedCode = $this->printedCode($result->virtualFiles);

        self::assertCount(1, $result->plan->operations);
        self::assertCount(0, $result->diagnostics);
        self::assertStringContainsString('@var int', $printedCode);
        self::assertStringContainsString('public const int DEFAULT_PORT = 25;', $printedCode);
        self::assertStringNotContainsString('public const string DEFAULT_PORT = 25;', $printedCode);
    }

    /**
     * Ensures a grouped class constant declaration is split when one constant is retyped.
     */
    public function testItSplitsGroupedClassConstantDeclarationsWhenOneConstantIsRetyped(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        $this->writeGroupedConfigFile($srcDirectory.'/Config.php');

        $result = PhpRetype::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
            clearCache: true,
        )->changeClassConstantType(
            className: 'App\\Config',
            constantName: 'DEFAULT_PORT',
            typeNode: new Identifier('int'),
            docType: 'int',
        );
        $printedCode = $this->printedCode($result->virtualFiles);

        self::assertCount(1, $result->plan->operations);
        self::assertCount(0, $result->diagnostics);
        self::assertStringContainsString('@var int', $printedCode);
        self::assertStringContainsString('public const int DEFAULT_PORT = 25;', $printedCode);
        self::assertStringContainsString('public const string FALLBACK_PORT = \'587\';', $printedCode);
    }

    /**
     * Ensures enum backing types can be changed.
     */
    public function testItChangesEnumBackingType(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        $this->writeStatusFile($srcDirectory.'/Status.php');

        $result = PhpRetype::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
            clearCache: true,
        )->changeEnumBackingType(
            enumName: 'App\\Status',
            typeNode: new Identifier('int'),
        );
        $printedCode = $this->printedCode($result->virtualFiles);

        self::assertCount(1, $result->plan->operations);
        self::assertCount(0, $result->diagnostics);
        self::assertStringContainsString('enum Status : int', $printedCode);
        self::assertStringNotContainsString('enum Status : string', $printedCode);
    }

    /**
     * Ensures class constant and enum backing type steps use in-memory partial refresh.
     */
    public function testItExecutesClassConstantAndEnumBackingTypeChangeStepsWithPartialRefresh(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        $this->writeGroupedConfigFile($srcDirectory.'/Config.php');
        $this->writeStatusFile($srcDirectory.'/Status.php');

        $build = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
            clearCache: true,
        );
        $context = RetypeStepContext::fromBuild($build);
        $retype = PhpRetype::fromBuild($build);

        $classConstantStep = $retype->executeStepClassConstantTypeChange(
            context: $context,
            className: 'App\\Config',
            constantName: 'DEFAULT_PORT',
            typeNode: new Identifier('int'),
            docType: 'int',
        );
        $this->assertAppliedStep($context, $classConstantStep->context);

        $enumBackingStep = $retype->executeStepEnumBackingTypeChange(
            context: $classConstantStep->context,
            enumName: 'App\\Status',
            typeNode: new Identifier('int'),
        );
        $printedCode = $this->printedCode($enumBackingStep->context->currentBuild->virtualFiles);

        $this->assertAppliedStep($classConstantStep->context, $enumBackingStep->context);
        self::assertCount(1, $classConstantStep->touchedFiles);
        self::assertCount(1, $enumBackingStep->touchedFiles);
        self::assertCount(0, $enumBackingStep->diagnostics);
        self::assertStringContainsString('public const int DEFAULT_PORT = 25;', $printedCode);
        self::assertStringContainsString('public const string FALLBACK_PORT = \'587\';', $printedCode);
        self::assertStringContainsString('enum Status : int', $printedCode);
    }

    /**
     * Ensures transactions refresh the graph across class constant and enum backing type changes.
     */
    public function testItAppliesClassConstantAndEnumBackingTypeChangesInsideATransaction(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        $this->writeGroupedConfigFile($srcDirectory.'/Config.php');
        $this->writeStatusFile($srcDirectory.'/Status.php');

        $transaction = PhpRetype::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
            clearCache: true,
        )->beginTransaction();

        $transaction->changeClassConstantType(
            className: 'App\\Config',
            constantName: 'DEFAULT_PORT',
            typeNode: new Identifier('int'),
            docType: 'int',
        );
        $transaction->changeEnumBackingType(
            enumName: 'App\\Status',
            typeNode: new Identifier('int'),
        );

        $transactionResult = $transaction->commit();
        $printedCode = $this->printedCode($transactionResult->virtualFiles);

        self::assertCount(2, $transactionResult->actionResults);
        self::assertCount(0, $transactionResult->diagnostics);
        self::assertStringContainsString('public const int DEFAULT_PORT = 25;', $printedCode);
        self::assertStringContainsString('public const string FALLBACK_PORT = \'587\';', $printedCode);
        self::assertStringContainsString('enum Status : int', $printedCode);
    }

    /**
     * Ensures invalid enum backing types are rejected before planning.
     */
    public function testItRejectsInvalidEnumBackingTypeBeforePlanning(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The native "bool" type is not valid for an enum backing type.');

        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        $this->writeStatusFile($srcDirectory.'/Status.php');

        PhpRetype::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
            clearCache: true,
        )->planEnumBackingTypeChange(
            enumName: 'App\\Status',
            typeNode: new Identifier('bool'),
        );
    }

    /**
     * Writes the config fixture.
     *
     * @param string $filePath the file path
     */
    private function writeConfigFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            final class Config
            {
                /**
                 * @var string
                 */
                public const string DEFAULT_PORT = 25;
            }
            PHP);
    }

    /**
     * Writes the grouped config fixture.
     *
     * @param string $filePath the file path
     */
    private function writeGroupedConfigFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            final class Config
            {
                /**
                 * @var string
                 */
                public const string DEFAULT_PORT = 25, FALLBACK_PORT = '587';
            }
            PHP);
    }

    /**
     * Writes the status enum fixture.
     *
     * @param string $filePath the file path
     */
    private function writeStatusFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            enum Status: string
            {
                case Active = '1';
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
     * Asserts a step refreshed the graph through the in-memory partial refresh path.
     *
     * @param RetypeStepContext $previousContext the previous step context
     * @param RetypeStepContext $nextContext     the refreshed step context
     */
    private function assertAppliedStep(RetypeStepContext $previousContext, RetypeStepContext $nextContext): void
    {
        self::assertNotSame($previousContext->currentBuild, $nextContext->currentBuild);
        self::assertFalse($nextContext->currentBuild->usedInMemoryFullFallback());
        self::assertTrue($nextContext->currentBuild->usedInMemoryPartialRefresh());
        self::assertNotNull($nextContext->currentBuild->buildReport->inMemoryRefreshWorkingSet);
        self::assertGreaterThan(0, count($nextContext->currentBuild->buildReport->inMemoryRefreshWorkingSet->filesToRebuildGraph));
        self::assertSame(0, $nextContext->currentBuild->buildReport->scannedFileCount);
        self::assertFalse($nextContext->currentBuild->buildReport->cacheWriteResult->isWritten());
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
