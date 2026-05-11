<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRetype\Tests\Integration;

use PhpNoobs\PhpRetype\Application\PhpRetype;
use PhpNoobs\PhpSource\VirtualPhpSourceFileCollection;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\UnionType;
use PHPUnit\Framework\TestCase;

/**
 * Covers function return type-change planning and AST application against real member-graph builds.
 */
final class PhpRetypeFunctionReturnTypeChangeIntegrationTest extends TestCase
{
    private string $workspace;

    /**
     * Creates a temporary integration workspace.
     */
    protected function setUp(): void
    {
        $this->workspace = sys_get_temp_dir().'/php-retype-function-return-'.str_replace('.', '', uniqid('', true));
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
     * Ensures function return retyping mutates the native type and direct `@return` type.
     */
    public function testItChangesFunctionReturnNativeTypeAndDocblockType(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        $this->writeFunctionFile($srcDirectory.'/functions.php');
        $this->writeSendResultFile($srcDirectory.'/SendResult.php');

        $retype = PhpRetype::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
        );

        $result = $retype->changeFunctionReturnType(
            functionName: 'App\\send_mail',
            typeNode: new Name('SendResult'),
            docType: 'SendResult',
        );
        $printedCode = $this->printedCode($result->virtualFiles);

        self::assertCount(1, $result->plan->operations);
        self::assertCount(0, $result->diagnostics);
        self::assertCount(0, $result->plan->diagnostics);
        self::assertSame(1, $this->updatedVirtualFileCount($result->virtualFiles));
        self::assertStringContainsString('@return SendResult', $printedCode);
        self::assertStringContainsString('function send_mail(): SendResult', $printedCode);
        self::assertStringNotContainsString('@return string', $printedCode);
        self::assertStringNotContainsString('function send_mail(): string', $printedCode);
    }

    /**
     * Ensures function return retyping can remove a native type while keeping the PHPDoc type explicit.
     */
    public function testItCanRemoveFunctionReturnNativeTypeWhileChangingDocblockType(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        $this->writeFunctionFile($srcDirectory.'/functions.php');

        $retype = PhpRetype::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
        );

        $result = $retype->changeFunctionReturnType(
            functionName: 'App\\send_mail',
            typeNode: null,
            docType: 'array{status: string}',
        );
        $printedCode = $this->printedCode($result->virtualFiles);

        self::assertCount(1, $result->plan->operations);
        self::assertCount(0, $result->diagnostics);
        self::assertStringContainsString('@return array{status: string}', $printedCode);
        self::assertStringContainsString('function send_mail()', $printedCode);
        self::assertStringNotContainsString('function send_mail():', $printedCode);
    }

    /**
     * Ensures `void` and `never` remain valid standalone return types.
     */
    public function testItAcceptsStandaloneVoidAndNeverReturnTypes(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        $this->writeFunctionFile($srcDirectory.'/functions.php');

        $retype = PhpRetype::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
        );

        $voidPlan = $retype->planFunctionReturnTypeChange(
            functionName: 'App\\send_mail',
            typeNode: new Identifier('void'),
            docType: 'void',
        );
        $neverPlan = $retype->planFunctionReturnTypeChange(
            functionName: 'App\\send_mail',
            typeNode: new Identifier('never'),
            docType: 'never',
        );

        self::assertCount(1, $voidPlan->operations);
        self::assertCount(1, $neverPlan->operations);
        self::assertCount(0, $voidPlan->diagnostics);
        self::assertCount(0, $neverPlan->diagnostics);
    }

    /**
     * Ensures invalid nullable return types are rejected before planning.
     */
    public function testItRejectsInvalidNullableReturnTypesBeforePlanning(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The nullable "void" return type is not valid.');

        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        $this->writeFunctionFile($srcDirectory.'/functions.php');

        $retype = PhpRetype::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
        );

        $retype->planFunctionReturnTypeChange(
            functionName: 'App\\send_mail',
            typeNode: new NullableType(new Identifier('void')),
            docType: '?void',
        );
    }

    /**
     * Ensures invalid union return types are rejected before planning.
     */
    public function testItRejectsVoidInsideUnionReturnTypesBeforePlanning(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The native "void" return type cannot be part of a union.');

        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        $this->writeFunctionFile($srcDirectory.'/functions.php');

        $retype = PhpRetype::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
        );

        $retype->planFunctionReturnTypeChange(
            functionName: 'App\\send_mail',
            typeNode: new UnionType([new Identifier('string'), new Identifier('void')]),
            docType: 'string|void',
        );
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

            /**
             * @return string
             */
            function send_mail(): string
            {
                return 'sent';
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
     * Counts updated virtual files.
     *
     * @param VirtualPhpSourceFileCollection $virtualFiles the virtual files to inspect
     */
    private function updatedVirtualFileCount(VirtualPhpSourceFileCollection $virtualFiles): int
    {
        $count = 0;

        foreach ($virtualFiles as $virtualFile) {
            if ($virtualFile->isUpdated()) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * Removes a directory recursively.
     *
     * @param string $directory the directory to remove
     */
    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);

        if (false === $items) {
            return;
        }

        foreach ($items as $item) {
            if ('.' === $item || '..' === $item) {
                continue;
            }

            $path = $directory.'/'.$item;

            if (is_dir($path)) {
                $this->removeDirectory($path);

                continue;
            }

            unlink($path);
        }

        rmdir($directory);
    }
}
