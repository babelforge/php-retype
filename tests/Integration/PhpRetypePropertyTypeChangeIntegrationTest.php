<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRetype\Tests\Integration;

use PhpNoobs\PhpRetype\Application\PhpRetype;
use PhpNoobs\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnostic;
use PhpNoobs\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnosticCollection;
use PhpNoobs\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnosticSeverity;
use PhpNoobs\PhpSource\VirtualPhpSourceFileCollection;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPUnit\Framework\TestCase;

/**
 * Covers property type-change planning and AST application against real member-graph builds.
 */
final class PhpRetypePropertyTypeChangeIntegrationTest extends TestCase
{
    private string $workspace;

    /**
     * Creates a temporary integration workspace.
     */
    protected function setUp(): void
    {
        $this->workspace = sys_get_temp_dir().'/php-retype-property-'.str_replace('.', '', uniqid('', true));
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
     * Ensures a full grouped property declaration can be retyped without splitting it.
     */
    public function testItChangesAllGroupedPropertyNativeTypesAndDocblockType(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        $this->writeGroupedPropertyFile($srcDirectory.'/Mailer.php');
        $this->writeTransportFile($srcDirectory.'/Transport.php');

        $result = PhpRetype::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
            clearCache: true,
        )->changePropertyType(
            className: 'App\\Mailer',
            propertyNames: ['transport', 'backupTransport', 'legacyTransport'],
            typeNode: new Name('Transport'),
            docType: 'Transport',
        );
        $printedCode = $this->printedCode($result->virtualFiles);

        self::assertCount(1, $result->plan->operations);
        self::assertCount(0, $result->diagnostics);
        self::assertStringContainsString('@var Transport', $printedCode);
        self::assertStringContainsString('private Transport $transport, $backupTransport, $legacyTransport;', $printedCode);
        self::assertStringNotContainsString('private string $transport, $backupTransport, $legacyTransport;', $printedCode);
    }

    /**
     * Ensures a partial grouped property declaration is split while preserving the remaining original type.
     */
    public function testItSplitsGroupedPropertyDeclarationsWhenOnlySomePropertiesAreRetyped(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        $this->writeGroupedPropertyFile($srcDirectory.'/Mailer.php');
        $this->writeTransportFile($srcDirectory.'/Transport.php');

        $result = PhpRetype::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
            clearCache: true,
        )->changePropertyType(
            className: 'App\\Mailer',
            propertyNames: ['transport', 'backupTransport'],
            typeNode: new Name('Transport'),
            docType: 'Transport',
        );
        $printedCode = $this->printedCode($result->virtualFiles);

        self::assertCount(1, $result->plan->operations);
        self::assertCount(0, $result->diagnostics);
        self::assertStringContainsString('@var Transport', $printedCode);
        self::assertStringContainsString('private Transport $transport, $backupTransport;', $printedCode);
        self::assertStringContainsString('@var string', $printedCode);
        self::assertStringContainsString('private string $legacyTransport;', $printedCode);
    }

    /**
     * Ensures promoted property parameters are retyped through their parameter node.
     */
    public function testItChangesPromotedPropertyNativeTypeAndDocblockType(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        $this->writePromotedPropertyFile($srcDirectory.'/Mailer.php');
        $this->writeTransportFile($srcDirectory.'/Transport.php');

        $result = PhpRetype::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
            clearCache: true,
        )->changePropertyType(
            className: 'App\\Mailer',
            propertyNames: 'transport',
            typeNode: new Name('Transport'),
            docType: 'Transport',
        );
        $printedCode = $this->printedCode($result->virtualFiles);

        self::assertCount(1, $result->plan->operations);
        self::assertCount(0, $result->diagnostics);
        self::assertStringContainsString('@var Transport', $printedCode);
        self::assertStringContainsString('public Transport $transport', $printedCode);
        self::assertStringNotContainsString('public string $transport', $printedCode);
    }

    /**
     * Ensures grouped property splitting preserves flags, attributes, defaults, and original metadata.
     */
    public function testItPreservesGroupedPropertyStructureWhenSplitting(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        $this->writeDecoratedGroupedPropertyFile($srcDirectory.'/Mailer.php');
        $this->writeTransportFile($srcDirectory.'/Transport.php');

        $result = PhpRetype::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
            clearCache: true,
        )->changePropertyType(
            className: 'App\\Mailer',
            propertyNames: ['transport', 'backupTransport'],
            typeNode: new Name('Transport'),
            docType: 'Transport',
        );
        $printedCode = $this->printedCode($result->virtualFiles);

        self::assertCount(1, $result->plan->operations);
        self::assertCount(0, $result->diagnostics);
        self::assertSame(2, substr_count($printedCode, '#[\\App\\TransportSlot]'));
        self::assertStringContainsString('@var Transport active transports', $printedCode);
        self::assertStringContainsString('protected static Transport $transport = \'smtp\', $backupTransport = \'sendmail\';', $printedCode);
        self::assertStringContainsString('@var string active transports', $printedCode);
        self::assertStringContainsString('protected static string $legacyTransport = \'mail\';', $printedCode);
    }

    /**
     * Ensures property retyping can remove a native type while changing the PHPDoc type.
     */
    public function testItCanRemovePropertyNativeTypeWhileChangingDocblockType(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        $this->writeGroupedPropertyFile($srcDirectory.'/Mailer.php');

        $result = PhpRetype::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
            clearCache: true,
        )->changePropertyType(
            className: 'App\\Mailer',
            propertyNames: ['transport', 'backupTransport', 'legacyTransport'],
            typeNode: null,
            docType: 'array{dsn: string}',
        );
        $printedCode = $this->printedCode($result->virtualFiles);

        self::assertCount(1, $result->plan->operations);
        self::assertCount(0, $result->diagnostics);
        self::assertStringContainsString('@var array{dsn: string}', $printedCode);
        self::assertStringContainsString('private $transport, $backupTransport, $legacyTransport;', $printedCode);
        self::assertStringNotContainsString('private string $transport', $printedCode);
    }

    /**
     * Ensures properties split across declarations produce a blocking diagnostic.
     */
    public function testItBlocksPropertyRetypeWhenRequestedPropertiesAreSplitAcrossDeclarations(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        $this->writeSplitPropertyFile($srcDirectory.'/Mailer.php');
        $this->writeTransportFile($srcDirectory.'/Transport.php');

        $result = PhpRetype::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
            clearCache: true,
        )->changePropertyType(
            className: 'App\\Mailer',
            propertyNames: ['transport', 'backupTransport'],
            typeNode: new Name('Transport'),
            docType: 'Transport',
        );
        $printedCode = $this->printedCode($result->virtualFiles);
        $diagnostic = $this->firstDiagnostic($result->plan->diagnostics);

        self::assertCount(0, $result->plan->operations);
        self::assertTrue($result->plan->diagnostics->hasErrors());
        self::assertNotNull($diagnostic);
        self::assertSame(RetypeDiagnosticSeverity::ERROR, $diagnostic->severity);
        self::assertSame('Requested properties are split across different declarations.', $diagnostic->message);
        self::assertStringContainsString('private string $transport;', $printedCode);
        self::assertStringContainsString('private string $backupTransport;', $printedCode);
        self::assertStringNotContainsString('private Transport $transport;', $printedCode);
    }

    /**
     * Ensures missing property declarations produce a precise non-blocking diagnostic.
     */
    public function testItReportsMissingPropertyDeclarationDiagnostic(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        $this->writeGroupedPropertyFile($srcDirectory.'/Mailer.php');
        $this->writeTransportFile($srcDirectory.'/Transport.php');

        $plan = PhpRetype::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
            clearCache: true,
        )->planPropertyTypeChange(
            className: 'App\\Mailer',
            propertyNames: 'missingTransport',
            typeNode: new Name('Transport'),
            docType: 'Transport',
        );
        $diagnostic = $this->firstDiagnostic($plan->diagnostics);

        self::assertCount(0, $plan->operations);
        self::assertFalse($plan->diagnostics->hasErrors());
        self::assertNotNull($diagnostic);
        self::assertSame(RetypeDiagnosticSeverity::WARNING, $diagnostic->severity);
        self::assertSame('Property declaration "App\\Mailer::$missingTransport" was not found.', $diagnostic->message);
    }

    /**
     * Ensures a transaction rebuilds its graph after a property split before planning the next action.
     */
    public function testItRebuildsTheTransactionGraphAfterAPropertySplit(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        $this->writeGroupedPropertyFile($srcDirectory.'/Mailer.php');
        $this->writeTransportFile($srcDirectory.'/Transport.php');

        $transaction = PhpRetype::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
            clearCache: true,
        )->beginTransaction();

        $transaction->changePropertyType(
            className: 'App\\Mailer',
            propertyNames: ['transport', 'backupTransport'],
            typeNode: new Name('Transport'),
            docType: 'Transport',
        );
        $transaction->changePropertyType(
            className: 'App\\Mailer',
            propertyNames: 'legacyTransport',
            typeNode: new Name('Transport'),
            docType: 'Transport',
        );

        $transactionResult = $transaction->commit();
        $printedCode = $this->printedCode($transactionResult->virtualFiles);

        self::assertCount(2, $transactionResult->actionResults);
        self::assertCount(0, $transactionResult->diagnostics);
        self::assertStringContainsString('private \\App\\Transport $transport, $backupTransport;', $printedCode);
        self::assertStringContainsString('private \\App\\Transport $legacyTransport;', $printedCode);
        self::assertStringNotContainsString('private string $legacyTransport;', $printedCode);
    }

    /**
     * Ensures invalid property native types are rejected before planning.
     */
    public function testItRejectsVoidPropertyTypeBeforePlanning(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The native "void" type is not valid for a property.');

        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        $this->writeGroupedPropertyFile($srcDirectory.'/Mailer.php');

        PhpRetype::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
            clearCache: true,
        )->planPropertyTypeChange(
            className: 'App\\Mailer',
            propertyNames: 'transport',
            typeNode: new Identifier('void'),
            docType: 'void',
        );
    }

    /**
     * Writes the grouped property fixture.
     *
     * @param string $filePath the file path
     */
    private function writeGroupedPropertyFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            final class Mailer
            {
                /**
                 * @var string
                 */
                private string $transport, $backupTransport, $legacyTransport;
            }
            PHP);
    }

    /**
     * Writes the promoted property fixture.
     *
     * @param string $filePath the file path
     */
    private function writePromotedPropertyFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            final class Mailer
            {
                public function __construct(
                    /**
                     * @var string
                     */
                    public string $transport,
                ) {
                }
            }
            PHP);
    }

    /**
     * Writes the decorated grouped property fixture.
     *
     * @param string $filePath the file path
     */
    private function writeDecoratedGroupedPropertyFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            use Attribute;

            #[Attribute]
            final class TransportSlot
            {
            }

            final class Mailer
            {
                /**
                 * @var string active transports
                 */
                #[TransportSlot]
                protected static string $transport = 'smtp', $backupTransport = 'sendmail', $legacyTransport = 'mail';
            }
            PHP);
    }

    /**
     * Writes the split property fixture.
     *
     * @param string $filePath the file path
     */
    private function writeSplitPropertyFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            final class Mailer
            {
                private string $transport;
                private string $backupTransport;
            }
            PHP);
    }

    /**
     * Writes the transport fixture.
     *
     * @param string $filePath the file path
     */
    private function writeTransportFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            final class Transport
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
     * Returns the first diagnostic from a collection.
     *
     * @param RetypeDiagnosticCollection $diagnostics the diagnostics to inspect
     */
    private function firstDiagnostic(RetypeDiagnosticCollection $diagnostics): ?RetypeDiagnostic
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
