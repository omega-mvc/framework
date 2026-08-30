<?php

/**
 * Part of Omega - Tests\Logging Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Logging;

use Omega\Logging\Exception\LogArgumentException;
use Omega\Logging\Stream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use ReflectionProperty;
use RuntimeException;
use Tests\Logging\Support\StringableMessage;

use function array_diff;
use function array_map;
use function chmod;
use function file_exists;
use function file_get_contents;
use function fopen;
use function fclose;
use function is_dir;
use function mkdir;
use function rmdir;
use function scandir;
use function str_contains;
use function str_ends_with;
use function sys_get_temp_dir;
use function touch;
use function uniqid;
use function unlink;
use function Omega\Application\slash;

/**
 * Class StreamTest
 *
 * This test suite verifies the behavior of the {@see Stream} log driver:
 * path handling, file operations, level thresholds, formatting options
 * and error conditions.
 *
 * @category   Tests
 * @package    Logging
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[CoversClass(Stream::class)]
final class StreamTest extends TestCase
{
    /** @var string Temporary directory used to isolate log file operations. */
    private string $tempDir;

    /**
     * Sets up the environment before each test method.
     *
     * Creates an isolated temporary directory inside the system temp path.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/omega-log-test-' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    /**
     * Tears down the environment after each test method.
     *
     * Removes the temporary directory created during setUp.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    /**
     * Test log writes to a generated file.
     *
     * @return void
     */
    public function testLogWritesToGeneratedFile(): void
    {
        $logger = new Stream($this->tempDir);

        $this->assertStringStartsWith($this->tempDir, $logger->getLogFilePath());
        $this->assertTrue(str_contains($logger->getLogFilePath(), 'log_'));
        $this->assertTrue(str_ends_with($logger->getLogFilePath(), '.txt'));

        $logger->log(LogLevel::INFO, 'hello world');

        $content = file_get_contents($logger->getLogFilePath());
        $this->assertTrue(str_contains($content, '[info]'));
        $this->assertTrue(str_contains($content, 'hello world'));
        $this->assertTrue(str_contains($logger->getLastLogLine(), 'hello world'));
    }

    /**
     * Test log writes to an explicit file path.
     *
     * @return void
     */
    public function testLogWritesToExplicitFile(): void
    {
        $file   = $this->tempDir . '/custom.log';
        $logger = new Stream($file);

        $this->assertSame($file, $logger->getLogFilePath());

        $logger->log(LogLevel::ERROR, 'explicit path');

        $this->assertTrue(str_contains(file_get_contents($file), 'explicit path'));
    }

    /**
     * Test log writes to a php stream.
     *
     * @return void
     */
    public function testLogWritesToPhpStream(): void
    {
        $logger = new Stream('php://stderr');

        $this->assertSame('php://stderr', $logger->getLogFilePath());

        $logger->log(LogLevel::WARNING, 'to stderr');

        $this->assertTrue(str_contains($logger->getLastLogLine(), 'to stderr'));
    }

    /**
     * Test log level threshold filtering.
     *
     * @return void
     */
    public function testLogRespectsThreshold(): void
    {
        $logger = new Stream($this->tempDir . '/threshold.log', LogLevel::ERROR);

        $logger->log(LogLevel::INFO, 'below threshold');
        $this->assertSame('', $logger->getLastLogLine());

        $logger->log(LogLevel::ERROR, 'at threshold');
        $this->assertTrue(str_contains($logger->getLastLogLine(), 'at threshold'));
    }

    /**
     * Test set log level threshold.
     *
     * @return void
     */
    public function testSetLogLevelThreshold(): void
    {
        $logger = new Stream($this->tempDir . '/threshold.log');

        $logger->setLogLevelThreshold(LogLevel::CRITICAL);
        $logger->log(LogLevel::INFO, 'filtered');

        $this->assertSame('', $logger->getLastLogLine());
    }

    /**
     * Test invalid log level throws.
     *
     * @return void
     */
    public function testInvalidLogLevelThrows(): void
    {
        $logger = new Stream($this->tempDir . '/invalid.log');

        try {
            $logger->log('invalid', 'message');
            $this->fail('Expected LogArgumentException was not thrown');
        } catch (LogArgumentException $e) {
            $this->assertSame('Invalid log level: invalid', $e->getMessage());
        }
    }

    /**
     * Test non-string log level throws.
     *
     * @return void
     */
    public function testNonStringLogLevelThrows(): void
    {
        $logger = new Stream($this->tempDir . '/invalid.log');

        $property = new ReflectionProperty(Stream::class, 'logLevels');
        $property->setAccessible(true);
        $property->setValue($logger, [0 => 0] + $property->getValue($logger));

        try {
            $logger->log(0, 'message');
            $this->fail('Expected LogArgumentException was not thrown');
        } catch (LogArgumentException $e) {
            $this->assertSame('Log level must be a string, integer given.', $e->getMessage());
        }
    }

    /**
     * Test write failure throws.
     *
     * @return void
     */
    public function testWriteFailureThrows(): void
    {
        $logger = new Stream($this->tempDir . '/write-fail.log');

        $readonly = $this->tempDir . '/readonly.bin';
        touch($readonly);
        $handle = fopen($readonly, 'r');

        $this->setProperty($logger, 'fileHandle', $handle);

        try {
            @$logger->write('boom');

            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException) {
            $this->assertTrue(true);
        } finally {
            fclose($handle);
        }
    }

    /**
     * Test write with a null handle is a no-op.
     *
     * @return void
     */
    public function testWriteWithNullHandleIsNoOp(): void
    {
        $logger = new Stream($this->tempDir . '/null-handle.log');

        $this->setProperty($logger, 'fileHandle', null);

        $logger->write('ignored');

        $this->assertSame('', $logger->getLastLogLine());
    }

    /**
     * Test write with a non-resource handle and flush frequency.
     *
     * @return void
     */
    public function testWriteWithNonResourceHandle(): void
    {
        $logger = new Stream($this->tempDir . '/non-resource.log', LogLevel::DEBUG, [
            'flushFrequency' => 1,
        ]);

        $this->setProperty($logger, 'fileHandle', 'not-a-resource');

        $logger->write('  hello  ');

        $this->assertSame('hello', $logger->getLastLogLine());
    }

    /**
     * Test write with a non-resource handle and no flush frequency.
     *
     * @return void
     */
    public function testWriteWithNonResourceHandleWithoutFlush(): void
    {
        $logger = new Stream($this->tempDir . '/non-resource.log');

        $this->setProperty($logger, 'fileHandle', 'not-a-resource');

        $logger->write('  hello  ');

        $this->assertSame('hello', $logger->getLastLogLine());
    }

    /**
     * Test write with a non-resource handle where the flush is not triggered.
     *
     * @return void
     */
    public function testWriteWithNonResourceHandleAndFlushMiss(): void
    {
        $logger = new Stream($this->tempDir . '/non-resource.log', LogLevel::DEBUG, [
            'flushFrequency' => 2,
        ]);

        $this->setProperty($logger, 'fileHandle', 'not-a-resource');

        $logger->write('first');

        $this->assertSame('first', $logger->getLastLogLine());
    }

    /**
     * Test flush frequency triggers a flush.
     *
     * @return void
     */
    public function testFlushFrequency(): void
    {
        $logger = new Stream($this->tempDir . '/flush.log', LogLevel::DEBUG, [
            'flushFrequency' => 2,
        ]);

        $logger->write('one');
        $this->assertSame('one', $logger->getLastLogLine());

        $logger->write('two');
        $this->assertSame('two', $logger->getLastLogLine());
    }

    /**
     * Test set date format.
     *
     * @return void
     */
    public function testSetDateFormat(): void
    {
        $logger = new Stream($this->tempDir . '/date-format.log');

        $logger->setDateFormat('Y-m-d');

        $this->assertTrue(true);
    }

    /**
     * Test set file handle failure throws.
     *
     * @return void
     */
    public function testSetFileHandleFailureThrows(): void
    {
        $logger = new Stream($this->tempDir . '/handle.log');

        $this->setProperty($logger, 'logFilePath', $this->tempDir);

        try {
            @$logger->setFileHandle('a');

            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException) {
            $this->assertTrue(true);
        }
    }

    /**
     * Test constructor fails when the directory cannot be created.
     *
     * @return void
     */
    public function testConstructorFailsWhenDirectoryCannotBeCreated(): void
    {
        $blocker = $this->tempDir . '/blocker.log';
        touch($blocker);

        try {
            @new Stream($blocker . '/subdir');

            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException) {
            $this->assertTrue(true);
        }
    }

    /**
     * Test constructor fails when the file is not writable.
     *
     * @return void
     */
    public function testConstructorFailsWhenFileNotWritable(): void
    {
        $file = $this->tempDir . '/locked.log';
        touch($file);
        chmod($file, 0o444);

        try {
            new Stream($file);

            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException) {
            $this->assertTrue(true);
        } finally {
            chmod($file, 0o644);
        }
    }

    /**
     * Test the constructor fails when the generated file is not writable.
     *
     * @return void
     */
    public function testConstructorFailsWhenFileNotWritableForGeneratedFile(): void
    {
        $file = $this->tempDir . '/locked.log';
        touch($file);
        chmod($file, 0o444);

        try {
            new Stream($this->tempDir, LogLevel::DEBUG, [
                'filename' => 'locked.log',
            ]);

            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException) {
            $this->assertTrue(true);
        } finally {
            chmod($file, 0o644);
        }
    }

    /**
     * Test the constructor creates nested directories for a generated file.
     *
     * @return void
     */
    public function testConstructorCreatesNestedDirectoryForGeneratedFile(): void
    {
        $logger = new Stream($this->tempDir . '/nested/logs');

        $this->assertTrue(is_dir($this->tempDir . '/nested/logs'));
        $this->assertStringStartsWith($this->tempDir . '/nested/logs', $logger->getLogFilePath());
    }

    /**
     * Test the constructor creates nested directories for an explicit file.
     *
     * @return void
     */
    public function testConstructorCreatesNestedDirectoryForExplicitFile(): void
    {
        $logger = new Stream($this->tempDir . '/nested/app.log');

        $this->assertTrue(is_dir($this->tempDir . '/nested'));
        $this->assertSame($this->tempDir . '/nested/app.log', $logger->getLogFilePath());
    }

    /**
     * Test the constructor opens an existing writable explicit file.
     *
     * @return void
     */
    public function testConstructorOpensExistingWritableExplicitFile(): void
    {
        $file = $this->tempDir . '/existing.log';
        touch($file);

        $logger = new Stream($file);

        $logger->log(LogLevel::INFO, 'appended');
        $this->assertTrue(str_contains(file_get_contents($file), 'appended'));
    }

    /**
     * Test the constructor opens an existing writable generated file.
     *
     * @return void
     */
    public function testConstructorOpensExistingWritableGeneratedFile(): void
    {
        $file = $this->tempDir . '/existing.log';
        touch($file);

        $logger = new Stream($this->tempDir, LogLevel::DEBUG, [
            'filename' => 'existing.log',
        ]);

        $logger->log(LogLevel::INFO, 'appended');
        $this->assertTrue(str_contains(file_get_contents($file), 'appended'));
    }

    /**
     * Test the constructor fails when the directory cannot be created for an explicit file.
     *
     * @return void
     */
    public function testConstructorFailsWhenDirectoryCannotBeCreatedForExplicitFile(): void
    {
        $blocker = $this->tempDir . '/blocker.log';
        touch($blocker);

        try {
            @new Stream($blocker . '/sub.log');

            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException) {
            $this->assertTrue(true);
        }
    }

    /**
     * Test set log file path with an explicit filename.
     *
     * @return void
     */
    public function testSetLogFilePathWithFilename(): void
    {
        $logger = new Stream($this->tempDir, LogLevel::DEBUG, [
            'filename' => 'app.log',
        ]);

        $this->assertTrue(str_ends_with($logger->getLogFilePath(), 'app.log'));
    }

    /**
     * Test set log file path with a filename without extension.
     *
     * @return void
     */
    public function testSetLogFilePathWithFilenameWithoutExtension(): void
    {
        $logger = new Stream($this->tempDir, LogLevel::DEBUG, [
            'filename'  => 'app',
            'extension' => 'log',
        ]);

        $this->assertTrue(str_ends_with($logger->getLogFilePath(), 'app.log'));
    }

    /**
     * Test set log file path with a prefix.
     *
     * @return void
     */
    public function testSetLogFilePathWithPrefix(): void
    {
        $logger = new Stream($this->tempDir, LogLevel::DEBUG, [
            'prefix' => 'custom_',
        ]);

        $this->assertTrue(str_contains($logger->getLogFilePath(), 'custom_'));
        $this->assertTrue(str_ends_with($logger->getLogFilePath(), '.txt'));
    }

    /**
     * Test set log file path with a txt filename.
     *
     * @return void
     */
    public function testSetLogFilePathWithTxtFilename(): void
    {
        $logger = new Stream($this->tempDir, LogLevel::DEBUG, [
            'filename' => 'app.txt',
        ]);

        $this->assertTrue(str_ends_with($logger->getLogFilePath(), 'app.txt'));
    }

    /**
     * Test set log file path with a non-string filename falls back to the extension.
     *
     * @return void
     */
    public function testSetLogFilePathWithNonStringFilename(): void
    {
        $logger = new Stream($this->tempDir, LogLevel::DEBUG, [
            'filename' => true,
        ]);

        $this->assertTrue(str_ends_with($logger->getLogFilePath(), '.txt'));
    }

    /**
     * Test log with context appended.
     *
     * @return void
     */
    public function testLogWithContextAppended(): void
    {
        $logger = new Stream($this->tempDir . '/context.log');

        $logger->log(LogLevel::INFO, 'hello', ['user' => 'omega']);

        $content = file_get_contents($logger->getLogFilePath());
        $this->assertTrue(str_contains($content, 'hello'));
        $this->assertTrue(str_contains($content, 'user:'));
    }

    /**
     * Test log without append context.
     *
     * @return void
     */
    public function testLogWithoutAppendContext(): void
    {
        $logger = new Stream($this->tempDir . '/no-context.log', LogLevel::DEBUG, [
            'appendContext' => false,
        ]);

        $logger->log(LogLevel::INFO, 'hello', ['user' => 'omega']);

        $content = file_get_contents($logger->getLogFilePath());
        $this->assertTrue(str_contains($content, 'hello'));
        $this->assertFalse(str_contains($content, 'user:'));
    }

    /**
     * Test log with a custom log format.
     *
     * @return void
     */
    public function testLogWithCustomLogFormat(): void
    {
        $logger = new Stream($this->tempDir . '/format.log', LogLevel::DEBUG, [
            'logFormat'     => '{date} | {level} | {level-padding} | {message} | {context}',
            'appendContext' => false,
        ]);

        $logger->log(LogLevel::INFO, 'formatted', ['x' => 1]);

        $content = file_get_contents($logger->getLogFilePath());
        $this->assertTrue(str_contains($content, '| INFO |'));
        $this->assertTrue(str_contains($content, 'formatted'));
        $this->assertTrue(str_contains($content, '{"x":1}'));
        $this->assertFalse(str_contains($content, '{date}'));
    }

    /**
     * Test log with a custom log format and an appended context.
     *
     * @return void
     */
    public function testLogWithCustomLogFormatAndAppendedContext(): void
    {
        $logger = new Stream($this->tempDir . '/format.log', LogLevel::DEBUG, [
            'logFormat' => '{level} | {message}',
        ]);

        $logger->log(LogLevel::WARNING, 'with context', ['user' => 'omega']);

        $content = file_get_contents($logger->getLogFilePath());
        $this->assertTrue(str_contains($content, 'WARNING | with context'));
        $this->assertTrue(str_contains($content, 'user:'));
    }

    /**
     * Test log with an empty string log format falls back to the default format.
     *
     * @return void
     */
    public function testLogWithEmptyStringLogFormat(): void
    {
        $logger = new Stream($this->tempDir . '/empty-format.log', LogLevel::DEBUG, [
            'logFormat' => '',
        ]);

        $logger->log(LogLevel::INFO, 'default format', ['user' => 'omega']);

        $content = file_get_contents($logger->getLogFilePath());
        $this->assertTrue(str_contains($content, '[info]'));
        $this->assertTrue(str_contains($content, 'default format'));
        $this->assertTrue(str_contains($content, 'user:'));
    }

    /**
     * Test log with an empty string log format and append context disabled.
     *
     * @return void
     */
    public function testLogWithEmptyStringLogFormatWithoutAppendContext(): void
    {
        $logger = new Stream($this->tempDir . '/empty-format.log', LogLevel::DEBUG, [
            'logFormat'     => '',
            'appendContext' => false,
        ]);

        $logger->log(LogLevel::INFO, 'default format', ['user' => 'omega']);

        $content = file_get_contents($logger->getLogFilePath());
        $this->assertTrue(str_contains($content, '[info]'));
        $this->assertFalse(str_contains($content, 'user:'));
    }

    /**
     * Test log with a stringable message and a custom log format.
     *
     * @return void
     */
    public function testLogWithStringableMessageAndCustomLogFormat(): void
    {
        $logger = new Stream($this->tempDir . '/stringable-format.log', LogLevel::DEBUG, [
            'logFormat' => '{message}',
        ]);

        $logger->log(LogLevel::INFO, new StringableMessage('stringable formatted'), ['user' => 'omega']);

        $this->assertTrue(str_contains(file_get_contents($logger->getLogFilePath()), 'stringable formatted'));
    }

    /**
     * Test log with a stringable message and append context disabled.
     *
     * @return void
     */
    public function testLogWithStringableMessageAndAppendContextDisabled(): void
    {
        $logger = new Stream($this->tempDir . '/stringable.log', LogLevel::DEBUG, [
            'appendContext' => false,
        ]);

        $logger->log(LogLevel::INFO, new StringableMessage('stringable bare'), ['user' => 'omega']);

        $content = file_get_contents($logger->getLogFilePath());
        $this->assertTrue(str_contains($content, 'stringable bare'));
        $this->assertFalse(str_contains($content, 'user:'));
    }

    /**
     * Test log with a stringable message.
     *
     * @return void
     */
    public function testLogWithStringableMessage(): void
    {
        $logger = new Stream($this->tempDir . '/stringable.log');

        $logger->log(LogLevel::INFO, new StringableMessage('stringable message'));

        $this->assertTrue(str_contains(file_get_contents($logger->getLogFilePath()), 'stringable message'));
    }

    /**
     * Test log with a stringable message and a custom log format without context.
     *
     * @return void
     */
    public function testLogWithStringableMessageAndCustomFormatWithoutContext(): void
    {
        $logger = new Stream($this->tempDir . '/stringable-format.log', LogLevel::DEBUG, [
            'logFormat' => '{message}',
        ]);

        $logger->log(LogLevel::INFO, new StringableMessage('stringable formatted'));

        $this->assertTrue(str_contains(file_get_contents($logger->getLogFilePath()), 'stringable formatted'));
    }

    /**
     * Test log with a stringable message and a custom log format without append context.
     *
     * @return void
     */
    public function testLogWithStringableMessageAndCustomFormatWithoutAppendContext(): void
    {
        $logger = new Stream($this->tempDir . '/stringable-format.log', LogLevel::DEBUG, [
            'logFormat'     => '{message}',
            'appendContext' => false,
        ]);

        $logger->log(LogLevel::INFO, new StringableMessage('stringable formatted'), ['x' => 1]);

        $content = file_get_contents($logger->getLogFilePath());
        $this->assertTrue(str_contains($content, 'stringable formatted'));
        $this->assertFalse(str_contains($content, 'x:'));
    }

    /**
     * Test log with a stringable message and an empty log format with context.
     *
     * @return void
     */
    public function testLogWithStringableMessageAndEmptyLogFormatWithContext(): void
    {
        $logger = new Stream($this->tempDir . '/stringable-format.log', LogLevel::DEBUG, [
            'logFormat' => '',
        ]);

        $logger->log(LogLevel::INFO, new StringableMessage('stringable formatted'), ['user' => 'omega']);

        $content = file_get_contents($logger->getLogFilePath());
        $this->assertTrue(str_contains($content, 'stringable formatted'));
        $this->assertTrue(str_contains($content, 'user:'));
    }

    /**
     * Test log with a stringable message and an empty log format without context.
     *
     * @return void
     */
    public function testLogWithStringableMessageAndEmptyLogFormatWithoutContext(): void
    {
        $logger = new Stream($this->tempDir . '/stringable-format.log', LogLevel::DEBUG, [
            'logFormat' => '',
        ]);

        $logger->log(LogLevel::INFO, new StringableMessage('stringable formatted'));

        $this->assertTrue(str_contains(file_get_contents($logger->getLogFilePath()), 'stringable formatted'));
    }

    /**
     * Test log with a stringable message and an empty log format without append context.
     *
     * @return void
     */
    public function testLogWithStringableMessageAndEmptyLogFormatWithoutAppendContext(): void
    {
        $logger = new Stream($this->tempDir . '/stringable-format.log', LogLevel::DEBUG, [
            'logFormat'     => '',
            'appendContext' => false,
        ]);

        $logger->log(LogLevel::INFO, new StringableMessage('stringable formatted'), ['x' => 1]);

        $content = file_get_contents($logger->getLogFilePath());
        $this->assertTrue(str_contains($content, 'stringable formatted'));
        $this->assertFalse(str_contains($content, 'x:'));
    }

    /**
     * Test log with a stringable message and a non-string log format.
     *
     * @return void
     */
    public function testLogWithStringableMessageAndNonStringLogFormat(): void
    {
        $logger = new Stream($this->tempDir . '/stringable-format.log', LogLevel::DEBUG, [
            'logFormat' => 123,
        ]);

        $logger->log(LogLevel::INFO, new StringableMessage('stringable formatted'), ['user' => 'omega']);

        $content = file_get_contents($logger->getLogFilePath());
        $this->assertTrue(str_contains($content, 'stringable formatted'));
        $this->assertTrue(str_contains($content, 'user:'));
    }

    /**
     * Test log with a custom log format without context.
     *
     * @return void
     */
    public function testLogWithCustomFormatWithoutContext(): void
    {
        $logger = new Stream($this->tempDir . '/format.log', LogLevel::DEBUG, [
            'logFormat' => '{level} | {message}',
        ]);

        $logger->log(LogLevel::INFO, 'formatted');

        $content = file_get_contents($logger->getLogFilePath());
        $this->assertTrue(str_contains($content, 'INFO | formatted'));
        $this->assertFalse(str_contains($content, '{message}'));
    }

    /**
     * Test log with an empty string log format without context.
     *
     * @return void
     */
    public function testLogWithEmptyLogFormatWithoutContext(): void
    {
        $logger = new Stream($this->tempDir . '/empty-format.log', LogLevel::DEBUG, [
            'logFormat' => '',
        ]);

        $logger->log(LogLevel::INFO, 'default format');

        $content = file_get_contents($logger->getLogFilePath());
        $this->assertTrue(str_contains($content, '[info]'));
        $this->assertTrue(str_contains($content, 'default format'));
    }

    /**
     * Test log with a non-string date format falls back to the default.
     *
     * @return void
     */
    public function testLogWithNonStringDateFormat(): void
    {
        $logger = new Stream($this->tempDir . '/fallback.log', LogLevel::DEBUG, [
            'dateFormat' => 123,
        ]);

        $logger->log(LogLevel::INFO, 'fallback format');

        $this->assertTrue(str_contains(file_get_contents($logger->getLogFilePath()), 'fallback format'));
    }

    /**
     * Test destructor with a non-resource handle.
     *
     * @return void
     */
    public function testDestructorWithNonResourceHandle(): void
    {
        $logger = new Stream($this->tempDir . '/destruct.log');

        $this->setProperty($logger, 'fileHandle', 'foo');
        $logger = null;

        $this->assertTrue(true);
    }

    /**
     * Set the value of a private or protected property.
     *
     * @param object $object   The object to modify.
     * @param string $property The property name.
     * @param mixed  $value    The value to set.
     * @return void
     */
    private function setProperty(object $object, string $property, mixed $value): void
    {
        $reflection = new ReflectionProperty($object, $property);
        $reflection->setAccessible(true);
        $reflection->setValue($object, $value);
    }

    /**
     * Recursively remove a directory.
     *
     * @param string $dir The directory to remove.
     * @return void
     */
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
