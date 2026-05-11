<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRetype\Tests\Integration;

use PhpNoobs\PhpRetype\Application\PhpRetype;
use PhpNoobs\PhpSource\VirtualPhpSourceFileCollection;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPUnit\Framework\TestCase;

/**
 * Covers method parameter type-change planning and AST application against real member-graph builds.
 */
final class PhpRetypeMethodParameterTypeChangeIntegrationTest extends TestCase
{
    private string $workspace;

    /**
     * Creates a temporary integration workspace.
     */
    protected function setUp(): void
    {
        $this->workspace = sys_get_temp_dir().'/php-retype-method-parameter-'.str_replace('.', '', uniqid('', true));
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
     * Ensures method parameter retyping mutates the native type and direct `@param` type.
     */
    public function testItChangesMethodParameterNativeTypeAndDocblockType(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        $this->writeMailerFile($srcDirectory.'/Mailer.php');
        $this->writeMessageFile($srcDirectory.'/Message.php');

        $retype = PhpRetype::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
        );

        $result = $retype->changeMethodParameterType(
            className: 'App\\Mailer',
            methodName: 'send',
            parameterName: 'message',
            typeNode: new Name('Message'),
            docType: 'Message',
            parameterIndex: 0,
        );
        $printedCode = $this->printedCode($result->virtualFiles);

        self::assertCount(1, $result->plan->operations);
        self::assertCount(0, $result->diagnostics);
        self::assertCount(0, $result->plan->diagnostics);
        self::assertSame(1, $this->updatedVirtualFileCount($result->virtualFiles));
        self::assertStringContainsString('@param Message $message', $printedCode);
        self::assertStringContainsString('public function send(Message $message): void', $printedCode);
        self::assertStringNotContainsString('@param string $message', $printedCode);
        self::assertStringNotContainsString('public function send(string $message): void', $printedCode);
    }

    /**
     * Ensures method parameter retyping can remove a native type while keeping the PHPDoc type explicit.
     */
    public function testItCanRemoveMethodParameterNativeTypeWhileChangingDocblockType(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        $this->writeMailerFile($srcDirectory.'/Mailer.php');

        $retype = PhpRetype::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
        );

        $result = $retype->changeMethodParameterType(
            className: 'App\\Mailer',
            methodName: 'send',
            parameterName: 'message',
            typeNode: null,
            docType: 'array{subject: string}',
            parameterIndex: 0,
        );
        $printedCode = $this->printedCode($result->virtualFiles);

        self::assertCount(1, $result->plan->operations);
        self::assertCount(0, $result->diagnostics);
        self::assertSame(1, $this->updatedVirtualFileCount($result->virtualFiles));
        self::assertStringContainsString('@param array{subject: string} $message', $printedCode);
        self::assertStringContainsString('public function send($message): void', $printedCode);
    }

    /**
     * Ensures invalid parameter native types are rejected before planning.
     */
    public function testItRejectsVoidParameterTypeBeforePlanning(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The native "void" type is not valid for a parameter.');

        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        $this->writeMailerFile($srcDirectory.'/Mailer.php');

        $retype = PhpRetype::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
        );

        $retype->planMethodParameterTypeChange(
            className: 'App\\Mailer',
            methodName: 'send',
            parameterName: 'message',
            typeNode: new Identifier('void'),
            docType: 'void',
            parameterIndex: 0,
        );
    }

    /**
     * Ensures function parameter retyping mutates the native type and direct `@param` type.
     */
    public function testItChangesFunctionParameterNativeTypeAndDocblockType(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        $this->writeFunctionFile($srcDirectory.'/functions.php');
        $this->writeMessageFile($srcDirectory.'/Message.php');

        $retype = PhpRetype::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
        );

        $result = $retype->changeFunctionParameterType(
            functionName: 'App\\send_mail',
            parameterName: 'message',
            typeNode: new Name('Message'),
            docType: 'Message',
            parameterIndex: 0,
        );
        $printedCode = $this->printedCode($result->virtualFiles);

        self::assertCount(1, $result->plan->operations);
        self::assertCount(0, $result->diagnostics);
        self::assertCount(0, $result->plan->diagnostics);
        self::assertSame(1, $this->updatedVirtualFileCount($result->virtualFiles));
        self::assertStringContainsString('@param Message $message', $printedCode);
        self::assertStringContainsString('function send_mail(Message $message): void', $printedCode);
        self::assertStringNotContainsString('@param string $message', $printedCode);
        self::assertStringNotContainsString('function send_mail(string $message): void', $printedCode);
    }

    /**
     * Ensures function parameter retyping supports declaration index targeting.
     */
    public function testItChangesFunctionParameterTypeAtIndex(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        $this->writeIndexedFunctionFile($srcDirectory.'/functions.php');

        $retype = PhpRetype::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
        );

        $result = $retype->changeFunctionParameterType(
            functionName: 'App\\send_mail',
            parameterName: 'transport',
            typeNode: new Identifier('array'),
            docType: 'array{dsn: string}',
            parameterIndex: 1,
        );
        $printedCode = $this->printedCode($result->virtualFiles);

        self::assertCount(1, $result->plan->operations);
        self::assertCount(0, $result->diagnostics);
        self::assertStringContainsString('@param array{dsn: string} $transport', $printedCode);
        self::assertStringContainsString('function send_mail(string $message, array $transport): void', $printedCode);
        self::assertStringContainsString('@param string $message', $printedCode);
        self::assertStringContainsString('string $message', $printedCode);
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
                /**
                 * @param string $message
                 */
                public function send(string $message): void
                {
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
             * @param string $message
             */
            function send_mail(string $message): void
            {
            }
            PHP);
    }

    /**
     * Writes the indexed function fixture.
     *
     * @param string $filePath the file path
     */
    private function writeIndexedFunctionFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            /**
             * @param string $message
             * @param string $transport
             */
            function send_mail(string $message, string $transport): void
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
