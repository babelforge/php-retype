<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRetype\Tests\Integration;

use PhpNoobs\PhpRetype\Application\PhpRetype;
use PhpNoobs\PhpSource\VirtualPhpSourceFileCollection;
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
