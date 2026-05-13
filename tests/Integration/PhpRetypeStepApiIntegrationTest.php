<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRetype\Tests\Integration;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphFactory;
use PhpNoobs\PhpRetype\Application\PhpRetype;
use PhpNoobs\PhpRetype\Application\RetypeStepExecutor;
use PhpNoobs\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnostic;
use PhpNoobs\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnosticCollection;
use PhpNoobs\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnosticSeverity;
use PhpNoobs\PhpRetype\Domain\Retype\Operation\RetypeOperationCollection;
use PhpNoobs\PhpRetype\Domain\Retype\Plan\RetypePlan;
use PhpNoobs\PhpRetype\Domain\Retype\Request\RetypeRequestInterface;
use PhpNoobs\PhpRetype\Domain\Retype\Step\RetypeStepContext;
use PhpNoobs\PhpRetype\Infrastructure\PhpParser\AstRetypePlanApplier;
use PhpNoobs\PhpSource\VirtualPhpSourceFileCollection;
use PhpParser\Node\Name;
use PHPUnit\Framework\TestCase;

/**
 * Covers the transaction-neutral retype step API used by external orchestrators.
 */
final class PhpRetypeStepApiIntegrationTest extends TestCase
{
    private string $workspace;

    /**
     * Creates a temporary integration workspace.
     */
    protected function setUp(): void
    {
        $this->workspace = sys_get_temp_dir().'/php-retype-step-api-'.str_replace('.', '', uniqid('', true));
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
     * Ensures supported retype steps can be chained through refreshed step contexts.
     */
    public function testItExecutesSupportedTypeChangeStepsAndRefreshesTheContext(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        $this->writeMailerFile($srcDirectory.'/Mailer.php');
        $this->writeFunctionFile($srcDirectory.'/functions.php');
        $this->writeMessageFile($srcDirectory.'/Message.php');
        $this->writeSendResultFile($srcDirectory.'/SendResult.php');

        $build = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
            clearCache: true,
        );
        $retype = PhpRetype::fromBuild($build);
        $context = RetypeStepContext::fromBuild($build);

        $methodParameterStep = $retype->executeStepMethodParameterTypeChange(
            context: $context,
            className: 'App\\Mailer',
            methodName: 'send',
            parameterName: 'message',
            typeNode: new Name('Message'),
            docType: 'Message',
            parameterIndex: 0,
        );
        $this->assertAppliedStep($methodParameterStep->applied, $methodParameterStep->requiresGraphRefresh, $context, $methodParameterStep->context);

        $functionParameterStep = $retype->executeStepFunctionParameterTypeChange(
            context: $methodParameterStep->context,
            functionName: 'App\\send_mail',
            parameterName: 'message',
            typeNode: new Name('Message'),
            docType: 'Message',
            parameterIndex: 0,
        );
        $this->assertAppliedStep(
            $functionParameterStep->applied,
            $functionParameterStep->requiresGraphRefresh,
            $methodParameterStep->context,
            $functionParameterStep->context,
        );

        $methodReturnStep = $retype->executeStepMethodReturnTypeChange(
            context: $functionParameterStep->context,
            className: 'App\\Mailer',
            methodName: 'send',
            typeNode: new Name('SendResult'),
            docType: 'SendResult',
        );
        $this->assertAppliedStep(
            $methodReturnStep->applied,
            $methodReturnStep->requiresGraphRefresh,
            $functionParameterStep->context,
            $methodReturnStep->context,
        );

        $functionReturnStep = $retype->executeStepFunctionReturnTypeChange(
            context: $methodReturnStep->context,
            functionName: 'App\\send_mail',
            typeNode: new Name('SendResult'),
            docType: 'SendResult',
        );
        $this->assertAppliedStep(
            $functionReturnStep->applied,
            $functionReturnStep->requiresGraphRefresh,
            $methodReturnStep->context,
            $functionReturnStep->context,
        );

        $printedCode = $this->printedCode($functionReturnStep->context->currentBuild->virtualFiles);

        self::assertCount(1, $methodParameterStep->touchedFiles);
        self::assertCount(1, $functionParameterStep->touchedFiles);
        self::assertCount(1, $methodReturnStep->touchedFiles);
        self::assertCount(1, $functionReturnStep->touchedFiles);
        self::assertCount(0, $functionReturnStep->diagnostics);
        self::assertStringContainsString('@param Message $message', $printedCode);
        self::assertStringContainsString('@return SendResult', $printedCode);
        self::assertStringContainsString('public function send(\\App\\Message $message): \\App\\SendResult', $printedCode);
        self::assertStringContainsString('function send_mail(\\App\\Message $message): \\App\\SendResult', $printedCode);
    }

    /**
     * Ensures plan errors prevent application and keep the existing step context.
     */
    public function testItDoesNotApplyAStepWhenThePlanContainsErrors(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        $this->writeMailerFile($srcDirectory.'/Mailer.php');

        $build = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
            clearCache: true,
        );
        $context = RetypeStepContext::fromBuild($build);
        $plan = new RetypePlan(
            request: new class implements RetypeRequestInterface {
            },
            operations: RetypeOperationCollection::empty(),
            diagnostics: RetypeDiagnosticCollection::empty()->add(new RetypeDiagnostic(
                severity: RetypeDiagnosticSeverity::ERROR,
                message: 'Planning failed.',
            )),
        );

        $step = new RetypeStepExecutor(new AstRetypePlanApplier())->execute($plan, $context);

        self::assertFalse($step->applied);
        self::assertFalse($step->requiresGraphRefresh);
        self::assertSame($context, $step->context);
        self::assertSame($context->currentBuild->virtualFiles, $step->retypeResult->virtualFiles);
        self::assertCount(1, $step->diagnostics);
        self::assertCount(0, $step->touchedFiles);
    }

    /**
     * Asserts the common state of an applied step.
     *
     * @param bool              $applied              whether the step was applied
     * @param bool              $requiresGraphRefresh whether the step refreshed the graph
     * @param RetypeStepContext $previousContext      the previous step context
     * @param RetypeStepContext $nextContext          the next step context
     */
    private function assertAppliedStep(
        bool $applied,
        bool $requiresGraphRefresh,
        RetypeStepContext $previousContext,
        RetypeStepContext $nextContext,
    ): void {
        self::assertTrue($applied);
        self::assertTrue($requiresGraphRefresh);
        self::assertNotSame($previousContext->currentBuild, $nextContext->currentBuild);
        self::assertTrue($nextContext->currentBuild->usedInMemoryFullFallback());
        self::assertFalse($nextContext->currentBuild->usedInMemoryPartialRefresh());
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
             *
             * @return string
             */
            function send_mail(string $message): string
            {
                return $message;
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
