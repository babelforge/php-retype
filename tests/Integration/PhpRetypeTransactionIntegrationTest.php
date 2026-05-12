<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRetype\Tests\Integration;

use PhpNoobs\PhpRetype\Application\PhpRetype;
use PhpNoobs\PhpRetype\Domain\Retype\Transaction\RetypeTransactionStatus;
use PhpNoobs\PhpSource\VirtualPhpSourceFileCollection;
use PhpParser\Node\Name;
use PHPUnit\Framework\TestCase;

/**
 * Covers standalone retype transactions against real member-graph builds.
 */
final class PhpRetypeTransactionIntegrationTest extends TestCase
{
    private string $workspace;

    /**
     * Creates a temporary integration workspace.
     */
    protected function setUp(): void
    {
        $this->workspace = sys_get_temp_dir().'/php-retype-transaction-'.str_replace('.', '', uniqid('', true));
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
     * Ensures a standalone transaction chains retype actions against refreshed builds.
     */
    public function testItCommitsAStandaloneRetypeTransaction(): void
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

        $parameterResult = $transaction->changeMethodParameterType(
            className: 'App\\Mailer',
            methodName: 'send',
            parameterName: 'message',
            typeNode: new Name('Message'),
            docType: 'Message',
            parameterIndex: 0,
        );
        $returnResult = $transaction->changeMethodReturnType(
            className: 'App\\Mailer',
            methodName: 'send',
            typeNode: new Name('SendResult'),
            docType: 'SendResult',
        );
        $transactionResult = $transaction->commit();
        $printedCode = $this->printedCode($transactionResult->virtualFiles);

        self::assertSame(RetypeTransactionStatus::COMMITTED, $transactionResult->status);
        self::assertSame(RetypeTransactionStatus::COMMITTED, $transaction->status());
        self::assertCount(1, $parameterResult->plan->operations);
        self::assertCount(1, $returnResult->plan->operations);
        self::assertCount(2, $transactionResult->actionResults);
        self::assertCount(0, $transactionResult->diagnostics);
        self::assertStringContainsString('@param Message $message', $printedCode);
        self::assertStringContainsString('@return SendResult', $printedCode);
        self::assertStringContainsString('public function send(\\App\\Message $message): \\App\\SendResult', $printedCode);
    }

    /**
     * Ensures rollback restores touched virtual files.
     */
    public function testItRollsBackAStandaloneRetypeTransaction(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        $this->writeMailerFile($srcDirectory.'/Mailer.php');
        $this->writeMessageFile($srcDirectory.'/Message.php');

        $transaction = PhpRetype::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
            clearCache: true,
        )->beginTransaction();

        $transaction->changeMethodParameterType(
            className: 'App\\Mailer',
            methodName: 'send',
            parameterName: 'message',
            typeNode: new Name('Message'),
            docType: 'Message',
            parameterIndex: 0,
        );

        $rollbackResult = $transaction->rollback();
        $printedCode = $this->printedCode($rollbackResult->virtualFiles);

        self::assertSame(RetypeTransactionStatus::ROLLED_BACK, $rollbackResult->status);
        self::assertSame(RetypeTransactionStatus::ROLLED_BACK, $transaction->status());
        self::assertStringContainsString('@param string $message', $printedCode);
        self::assertStringContainsString('public function send(string $message): string', $printedCode);
        self::assertStringNotContainsString('@param Message $message', $printedCode);
        self::assertStringNotContainsString('\\App\\Message $message', $printedCode);
    }

    /**
     * Ensures commit-and-save writes every updated physical source file.
     */
    public function testItCommitsAndSavesEveryUpdatedSourceFile(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';
        $mailerFilePath = $srcDirectory.'/Mailer.php';

        mkdir($srcDirectory, 0o777, true);
        $this->writeMailerFile($mailerFilePath);
        $this->writeMessageFile($srcDirectory.'/Message.php');

        $transaction = PhpRetype::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
            clearCache: true,
        )->beginTransaction();

        $transaction->changeMethodParameterType(
            className: 'App\\Mailer',
            methodName: 'send',
            parameterName: 'message',
            typeNode: new Name('Message'),
            docType: 'Message',
            parameterIndex: 0,
        );

        $result = $transaction->commitAndSave();
        $contents = (string) file_get_contents($mailerFilePath);

        self::assertSame(RetypeTransactionStatus::COMMITTED, $result->status);
        self::assertStringContainsString('@param Message $message', $contents);
        self::assertStringContainsString('public function send(\\App\\Message $message): string', $contents);
        self::assertStringNotContainsString('@param string $message', $contents);
    }

    /**
     * Ensures targeted commit-and-save writes only the requested physical source file.
     */
    public function testItCommitsAndSavesOneUpdatedSourceFile(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';
        $mailerFilePath = $srcDirectory.'/Mailer.php';
        $notifierFilePath = $srcDirectory.'/Notifier.php';

        mkdir($srcDirectory, 0o777, true);
        $this->writeMailerFile($mailerFilePath);
        $this->writeNotifierFile($notifierFilePath);
        $this->writeMessageFile($srcDirectory.'/Message.php');

        $transaction = PhpRetype::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
            clearCache: true,
        )->beginTransaction();

        $transaction->changeMethodParameterType(
            className: 'App\\Mailer',
            methodName: 'send',
            parameterName: 'message',
            typeNode: new Name('Message'),
            docType: 'Message',
            parameterIndex: 0,
        );
        $transaction->changeMethodParameterType(
            className: 'App\\Notifier',
            methodName: 'notify',
            parameterName: 'message',
            typeNode: new Name('Message'),
            docType: 'Message',
            parameterIndex: 0,
        );

        $result = $transaction->commitAndSaveSourceFile($mailerFilePath);
        $mailerContents = (string) file_get_contents($mailerFilePath);
        $notifierContents = (string) file_get_contents($notifierFilePath);

        self::assertSame(RetypeTransactionStatus::COMMITTED, $result->status);
        self::assertStringContainsString('public function send(\\App\\Message $message): string', $mailerContents);
        self::assertStringNotContainsString('public function send(string $message): string', $mailerContents);
        self::assertStringContainsString('public function notify(string $message): string', $notifierContents);
        self::assertStringNotContainsString('public function notify(\\App\\Message $message): string', $notifierContents);
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
                 *
                 * @return string
                 */
                public function send(string $message): string
                {
                    return $message;
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
     * Writes the notifier fixture.
     *
     * @param string $filePath the file path
     */
    private function writeNotifierFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            final class Notifier
            {
                /**
                 * @param string $message
                 *
                 * @return string
                 */
                public function notify(string $message): string
                {
                    return $message;
                }
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
