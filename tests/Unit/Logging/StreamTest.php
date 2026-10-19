<?php

declare(strict_types=1);

namespace Tests\Logging;

use Omega\Logging\Exception\LogArgumentException;
use Omega\Logging\Stream;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\LogLevel;
use ReflectionProperty;
use RuntimeException;
use Tests\Logging\Support\StringableMessage;
use Tests\TestCase;

use function array_diff;
use function array_map;
use function chmod;
use function fclose;
use function file_get_contents;
use function fopen;
use function is_dir;
use function mkdir;
use function rmdir;
use function scandir;
use function sys_get_temp_dir;
use function touch;
use function uniqid;
use function unlink;
use function Omega\Application\slash;

#[CoversClass(Stream::class)]
final class StreamTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir() . '/omega-log-test-' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);

        parent::tearDown();
    }

    public function testWritesToAGeneratedFile(): void
    {
        $logger = new Stream($this->tempDir);

        $this->assertStringStartsWith($this->tempDir . '/', $logger->getLogFilePath());
        $this->assertStringContainsString('log_', $logger->getLogFilePath());
        $this->assertStringEndsWith('.txt', $logger->getLogFilePath());

        $logger->log(LogLevel::INFO, 'hello world');

        $content = (string) file_get_contents($logger->getLogFilePath());
        $this->assertStringContainsString('[info]', $content);
        $this->assertStringContainsString('hello world', $content);
        $this->assertStringContainsString('hello world', $logger->getLastLogLine());
    }

    public function testWritesToAnExplicitFilePath(): void
    {
        $file   = $this->tempDir . '/custom.log';
        $logger = new Stream($file);

        $this->assertSame($file, $logger->getLogFilePath());

        $logger->log(LogLevel::ERROR, 'explicit path');

        $this->assertStringContainsString('explicit path', (string) file_get_contents($file));
    }

    public function testWritesToAPhpStream(): void
    {
        $logger = new Stream('php://stderr');

        $this->assertSame('php://stderr', $logger->getLogFilePath());

        $logger->log(LogLevel::WARNING, 'to stderr');

        $this->assertStringContainsString('to stderr', $logger->getLastLogLine());
    }

    public function testRespectsTheLogLevelThreshold(): void
    {
        $logger = new Stream($this->tempDir . '/threshold.log', LogLevel::ERROR);

        $logger->log(LogLevel::INFO, 'below threshold');

        $this->assertSame('', $logger->getLastLogLine());

        $logger->log(LogLevel::ERROR, 'at threshold');

        $this->assertStringContainsString('at threshold', $logger->getLastLogLine());
    }

    public function testCanSetTheLogLevelThreshold(): void
    {
        $logger = new Stream($this->tempDir . '/threshold.log');

        $logger->setLogLevelThreshold(LogLevel::CRITICAL);
        $logger->log(LogLevel::INFO, 'filtered');

        $this->assertSame('', $logger->getLastLogLine());
    }

    public function testThrowsAnExceptionForAnInvalidLogLevel(): void
    {
        $logger = new Stream($this->tempDir . '/invalid.log');

        $this->expectException(LogArgumentException::class);
        $this->expectExceptionMessage('Invalid log level: invalid');

        $logger->log('invalid', 'message');
    }

    public function testThrowsAnExceptionForANonStringLogLevel(): void
    {
        $logger = new Stream($this->tempDir . '/invalid.log');

        $property = new ReflectionProperty(Stream::class, 'logLevels');
        $levels = $property->getValue($logger);
        $this->assertIsArray($levels);
        $property->setValue($logger, [0 => 0] + $levels);

        $this->expectException(LogArgumentException::class);
        $this->expectExceptionMessage('Log level must be a string, integer given.');

        $logger->log(0, 'message');
    }

    public function testThrowsAnExceptionWhenAWriteFails(): void
    {
        $logger = new Stream($this->tempDir . '/write-fail.log');

        $readonly = $this->tempDir . '/readonly.bin';
        touch($readonly);
        $handle = fopen($readonly, 'r');
        $this->assertIsResource($handle);

        $this->setProperty($logger, 'fileHandle', $handle);

        $this->expectException(RuntimeException::class);

        set_error_handler(static fn (): bool => true);

        try {
            @$logger->write('boom');
        } finally {
            restore_error_handler();
            fclose($handle);
        }
    }

    public function testDoesNothingWhenWritingWithANullHandle(): void
    {
        $logger = new Stream($this->tempDir . '/null-handle.log');

        $this->setProperty($logger, 'fileHandle', null);

        $logger->write('ignored');

        $this->assertSame('', $logger->getLastLogLine());
    }

    public function testWritesWhenTheHandleIsANonResource(): void
    {
        $logger = new Stream($this->tempDir . '/non-resource.log', LogLevel::DEBUG, [
            'flushFrequency' => 1,
        ]);

        $this->setProperty($logger, 'fileHandle', 'not-a-resource');

        $logger->write('  hello  ');

        $this->assertSame('hello', $logger->getLastLogLine());
    }

    public function testWritesWhenTheHandleIsANonResourceWithoutAFlushFrequency(): void
    {
        $logger = new Stream($this->tempDir . '/non-resource.log');

        $this->setProperty($logger, 'fileHandle', 'not-a-resource');

        $logger->write('  hello  ');

        $this->assertSame('hello', $logger->getLastLogLine());
    }

    public function testWritesWhenAFlushIsNotTriggered(): void
    {
        $logger = new Stream($this->tempDir . '/non-resource.log', LogLevel::DEBUG, [
            'flushFrequency' => 2,
        ]);

        $this->setProperty($logger, 'fileHandle', 'not-a-resource');

        $logger->write('first');

        $this->assertSame('first', $logger->getLastLogLine());
    }

    public function testFlushesAccordingToTheFlushFrequency(): void
    {
        $logger = new Stream($this->tempDir . '/flush.log', LogLevel::DEBUG, [
            'flushFrequency' => 2,
        ]);

        $logger->write('one');

        $this->assertSame('one', $logger->getLastLogLine());

        $logger->write('two');

        $this->assertSame('two', $logger->getLastLogLine());
    }

    public function testCanSetTheDateFormat(): void
    {
        $logger = new Stream($this->tempDir . '/date-format.log');

        $logger->setDateFormat('Y-m-d');
        $logger->log(LogLevel::INFO, 'dated');

        $content = (string) file_get_contents($logger->getLogFilePath());
        $this->assertMatchesRegularExpression('/\[\d{4}-\d{2}-\d{2}\]/', $content);
        $this->assertStringContainsString('dated', $content);
    }

    public function testThrowsAnExceptionWhenTheFileHandleCannotBeSet(): void
    {
        $logger = new Stream($this->tempDir . '/handle.log');

        $this->setProperty($logger, 'logFilePath', $this->tempDir);

        $this->expectException(RuntimeException::class);

        @$logger->setFileHandle('a');
    }

    public function testFailsWhenTheDirectoryCannotBeCreated(): void
    {
        $blocker = $this->tempDir . '/blocker.log';
        touch($blocker);

        $this->expectException(RuntimeException::class);

        @new Stream($blocker . '/subdir');
    }

    public function testFailsWhenTheFileIsNotWritable(): void
    {
        $file = $this->tempDir . '/locked.log';
        touch($file);
        chmod($file, 0o444);

        $this->expectException(RuntimeException::class);

        try {
            new Stream($file);
        } finally {
            chmod($file, 0o644);
        }
    }

    public function testFailsWhenAGeneratedFileIsNotWritable(): void
    {
        $file = $this->tempDir . '/locked.log';
        touch($file);
        chmod($file, 0o444);

        $this->expectException(RuntimeException::class);

        try {
            new Stream($this->tempDir, LogLevel::DEBUG, [
                'filename' => 'locked.log',
            ]);
        } finally {
            chmod($file, 0o644);
        }
    }

    public function testCreatesANestedDirectoryForAGeneratedFile(): void
    {
        $logger = new Stream($this->tempDir . '/nested/logs');

        $this->assertTrue(is_dir($this->tempDir . '/nested/logs'));
        $this->assertStringStartsWith($this->tempDir . '/nested/logs', $logger->getLogFilePath());
    }

    public function testCreatesANestedDirectoryForAnExplicitFile(): void
    {
        $logger = new Stream($this->tempDir . '/nested/app.log');

        $this->assertTrue(is_dir($this->tempDir . '/nested'));
        $this->assertSame($this->tempDir . '/nested/app.log', $logger->getLogFilePath());
    }

    public function testOpensAnExistingWritableExplicitFile(): void
    {
        $file = $this->tempDir . '/existing.log';
        touch($file);

        $logger = new Stream($file);

        $logger->log(LogLevel::INFO, 'appended');

        $this->assertStringContainsString('appended', (string) file_get_contents($file));
    }

    public function testOpensAnExistingWritableGeneratedFile(): void
    {
        $file = $this->tempDir . '/existing.log';
        touch($file);

        $logger = new Stream($this->tempDir, LogLevel::DEBUG, [
            'filename' => 'existing.log',
        ]);

        $logger->log(LogLevel::INFO, 'appended');

        $this->assertStringContainsString('appended', (string) file_get_contents($file));
    }

    public function testFailsWhenTheDirectoryCannotBeCreatedForAnExplicitFile(): void
    {
        $blocker = $this->tempDir . '/blocker.log';
        touch($blocker);

        $this->expectException(RuntimeException::class);

        @new Stream($blocker . '/sub.log');
    }

    public function testSetsTheLogFilePathWithAFilename(): void
    {
        $logger = new Stream($this->tempDir, LogLevel::DEBUG, [
            'filename' => 'app.log',
        ]);

        $this->assertStringEndsWith('app.log', $logger->getLogFilePath());
    }

    public function testSetsTheLogFilePathWithAFilenameWithoutExtension(): void
    {
        $logger = new Stream($this->tempDir, LogLevel::DEBUG, [
            'filename'  => 'app',
            'extension' => 'log',
        ]);

        $this->assertStringEndsWith('app.log', $logger->getLogFilePath());
    }

    public function testSetsTheLogFilePathWithAPrefix(): void
    {
        $logger = new Stream($this->tempDir, LogLevel::DEBUG, [
            'prefix' => 'custom_',
        ]);

        $this->assertStringContainsString('custom_', $logger->getLogFilePath());
        $this->assertStringEndsWith('.txt', $logger->getLogFilePath());
    }

    public function testSetsTheLogFilePathWithATxtFilename(): void
    {
        $logger = new Stream($this->tempDir, LogLevel::DEBUG, [
            'filename' => 'app.txt',
        ]);

        $this->assertStringEndsWith('app.txt', $logger->getLogFilePath());
    }

    public function testFallsBackToTheExtensionForANonStringFilename(): void
    {
        $logger = new Stream($this->tempDir, LogLevel::DEBUG, [
            'filename' => true,
        ]);

        $this->assertStringEndsWith('.txt', $logger->getLogFilePath());
    }

    public function testAppendsContextToTheLog(): void
    {
        $logger = new Stream($this->tempDir . '/context.log');

        $logger->log(LogLevel::INFO, 'hello', ['user' => 'omega']);

        $content = (string) file_get_contents($logger->getLogFilePath());
        $this->assertStringContainsString('hello', $content);
        $this->assertStringContainsString('user:', $content);
    }

    public function testDoesNotAppendContextWhenDisabled(): void
    {
        $logger = new Stream($this->tempDir . '/no-context.log', LogLevel::DEBUG, [
            'appendContext' => false,
        ]);

        $logger->log(LogLevel::INFO, 'hello', ['user' => 'omega']);

        $content = (string) file_get_contents($logger->getLogFilePath());
        $this->assertStringContainsString('hello', $content);
        $this->assertStringNotContainsString('user:', $content);
    }

    public function testUsesACustomLogFormat(): void
    {
        $logger = new Stream($this->tempDir . '/format.log', LogLevel::DEBUG, [
            'logFormat'     => '{date} | {level} | {level-padding} | {message} | {context}',
            'appendContext' => false,
        ]);

        $logger->log(LogLevel::INFO, 'formatted', ['x' => 1]);

        $content = (string) file_get_contents($logger->getLogFilePath());
        $this->assertStringContainsString('| INFO |', $content);
        $this->assertStringContainsString('formatted', $content);
        $this->assertStringContainsString('{"x":1}', $content);
        $this->assertStringNotContainsString('{date}', $content);
    }

    public function testUsesACustomLogFormatWithAnAppendedContext(): void
    {
        $logger = new Stream($this->tempDir . '/format.log', LogLevel::DEBUG, [
            'logFormat' => '{level} | {message}',
        ]);

        $logger->log(LogLevel::WARNING, 'with context', ['user' => 'omega']);

        $content = (string) file_get_contents($logger->getLogFilePath());
        $this->assertStringContainsString('WARNING | with context', $content);
        $this->assertStringContainsString('user:', $content);
    }

    public function testFallsBackToTheDefaultFormatForAnEmptyStringLogFormat(): void
    {
        $logger = new Stream($this->tempDir . '/empty-format.log', LogLevel::DEBUG, [
            'logFormat' => '',
        ]);

        $logger->log(LogLevel::INFO, 'default format', ['user' => 'omega']);

        $content = (string) file_get_contents($logger->getLogFilePath());
        $this->assertStringContainsString('[info]', $content);
        $this->assertStringContainsString('default format', $content);
        $this->assertStringContainsString('user:', $content);
    }

    public function testFallsBackToTheDefaultFormatWithoutAppendingContext(): void
    {
        $logger = new Stream($this->tempDir . '/empty-format.log', LogLevel::DEBUG, [
            'logFormat'     => '',
            'appendContext' => false,
        ]);

        $logger->log(LogLevel::INFO, 'default format', ['user' => 'omega']);

        $content = (string) file_get_contents($logger->getLogFilePath());
        $this->assertStringContainsString('[info]', $content);
        $this->assertStringNotContainsString('user:', $content);
    }

    public function testLogsAStringableMessageWithACustomLogFormat(): void
    {
        $logger = new Stream($this->tempDir . '/stringable-format.log', LogLevel::DEBUG, [
            'logFormat' => '{message}',
        ]);

        $logger->log(LogLevel::INFO, new StringableMessage('stringable formatted'), ['user' => 'omega']);

        $this->assertStringContainsString(
            'stringable formatted',
            (string) file_get_contents($logger->getLogFilePath())
        );
    }

    public function testLogsAStringableMessageWithoutAppendContext(): void
    {
        $logger = new Stream($this->tempDir . '/stringable.log', LogLevel::DEBUG, [
            'appendContext' => false,
        ]);

        $logger->log(LogLevel::INFO, new StringableMessage('stringable bare'), ['user' => 'omega']);

        $content = (string) file_get_contents($logger->getLogFilePath());
        $this->assertStringContainsString('stringable bare', $content);
        $this->assertStringNotContainsString('user:', $content);
    }

    public function testLogsAStringableMessage(): void
    {
        $logger = new Stream($this->tempDir . '/stringable.log');

        $logger->log(LogLevel::INFO, new StringableMessage('stringable message'));

        $this->assertStringContainsString(
            'stringable message',
            (string) file_get_contents($logger->getLogFilePath())
        );
    }

    public function testLogsAStringableMessageWithACustomFormatWithoutContext(): void
    {
        $logger = new Stream($this->tempDir . '/stringable-format.log', LogLevel::DEBUG, [
            'logFormat' => '{message}',
        ]);

        $logger->log(LogLevel::INFO, new StringableMessage('stringable formatted'));

        $this->assertStringContainsString(
            'stringable formatted',
            (string) file_get_contents($logger->getLogFilePath())
        );
    }

    public function testLogsAStringableMessageWithACustomFormatWithoutAppendContext(): void
    {
        $logger = new Stream($this->tempDir . '/stringable-format.log', LogLevel::DEBUG, [
            'logFormat'     => '{message}',
            'appendContext' => false,
        ]);

        $logger->log(LogLevel::INFO, new StringableMessage('stringable formatted'), ['x' => 1]);

        $content = (string) file_get_contents($logger->getLogFilePath());
        $this->assertStringContainsString('stringable formatted', $content);
        $this->assertStringNotContainsString('x:', $content);
    }

    public function testLogsAStringableMessageWithAnEmptyLogFormatAndContext(): void
    {
        $logger = new Stream($this->tempDir . '/stringable-format.log', LogLevel::DEBUG, [
            'logFormat' => '',
        ]);

        $logger->log(LogLevel::INFO, new StringableMessage('stringable formatted'), ['user' => 'omega']);

        $content = (string) file_get_contents($logger->getLogFilePath());
        $this->assertStringContainsString('stringable formatted', $content);
        $this->assertStringContainsString('user:', $content);
    }

    public function testLogsAStringableMessageWithAnEmptyLogFormatWithoutContext(): void
    {
        $logger = new Stream($this->tempDir . '/stringable-format.log', LogLevel::DEBUG, [
            'logFormat' => '',
        ]);

        $logger->log(LogLevel::INFO, new StringableMessage('stringable formatted'));

        $this->assertStringContainsString(
            'stringable formatted',
            (string) file_get_contents($logger->getLogFilePath())
        );
    }

    public function testLogsAStringableMessageWithAnEmptyLogFormatWithoutAppendContext(): void
    {
        $logger = new Stream($this->tempDir . '/stringable-format.log', LogLevel::DEBUG, [
            'logFormat'     => '',
            'appendContext' => false,
        ]);

        $logger->log(LogLevel::INFO, new StringableMessage('stringable formatted'), ['x' => 1]);

        $content = (string) file_get_contents($logger->getLogFilePath());
        $this->assertStringContainsString('stringable formatted', $content);
        $this->assertStringNotContainsString('x:', $content);
    }

    public function testLogsAStringableMessageWithANonStringLogFormat(): void
    {
        $logger = new Stream($this->tempDir . '/stringable-format.log', LogLevel::DEBUG, [
            'logFormat' => 123,
        ]);

        $logger->log(LogLevel::INFO, new StringableMessage('stringable formatted'), ['user' => 'omega']);

        $content = (string) file_get_contents($logger->getLogFilePath());
        $this->assertStringContainsString('stringable formatted', $content);
        $this->assertStringContainsString('user:', $content);
    }

    public function testUsesACustomLogFormatWithoutContext(): void
    {
        $logger = new Stream($this->tempDir . '/format.log', LogLevel::DEBUG, [
            'logFormat' => '{level} | {message}',
        ]);

        $logger->log(LogLevel::INFO, 'formatted');

        $content = (string) file_get_contents($logger->getLogFilePath());
        $this->assertStringContainsString('INFO | formatted', $content);
        $this->assertStringNotContainsString('{message}', $content);
    }

    public function testUsesAnEmptyLogFormatWithoutContext(): void
    {
        $logger = new Stream($this->tempDir . '/empty-format.log', LogLevel::DEBUG, [
            'logFormat' => '',
        ]);

        $logger->log(LogLevel::INFO, 'default format');

        $content = (string) file_get_contents($logger->getLogFilePath());
        $this->assertStringContainsString('[info]', $content);
        $this->assertStringContainsString('default format', $content);
    }

    public function testFallsBackToTheDefaultDateFormatForANonStringDateFormat(): void
    {
        $logger = new Stream($this->tempDir . '/fallback.log', LogLevel::DEBUG, [
            'dateFormat' => 123,
        ]);

        $logger->log(LogLevel::INFO, 'fallback format');

        $this->assertStringContainsString(
            'fallback format',
            (string) file_get_contents($logger->getLogFilePath())
        );
    }

    public function testHandlesANonResourceHandleOnDestruction(): void
    {
        $logger = new Stream($this->tempDir . '/destruct.log');

        $this->setProperty($logger, 'fileHandle', 'foo');
        $logger = null;

        $this->addToAssertionCount(1);
    }

    private function setProperty(object $object, string $property, mixed $value): void
    {
        $reflection = new ReflectionProperty($object, $property);
        $reflection->setValue($object, $value);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        array_map(
            function (string $entry) use ($dir): void {
                $path = $dir . slash(path: '/') . $entry;

                if (is_dir($path)) {
                    $this->removeDirectory($path);
                } else {
                    @unlink($path);
                }
            },
            array_diff(scandir($dir), ['.', '..'])
        );

        @rmdir($dir);
    }
}
