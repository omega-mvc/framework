<?php

declare(strict_types=1);

namespace Tests\Logging;

use Omega\Logging\Exception\LogArgumentException;
use Omega\Logging\Stream;
use Psr\Log\LogLevel;
use ReflectionProperty;
use RuntimeException;
use Tests\Logging\Support\StringableMessage;

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

covers(Stream::class);

beforeEach(function (): void {
    $this->tempDir = sys_get_temp_dir() . '/omega-log-test-' . uniqid();
    mkdir($this->tempDir, 0777, true);
});

afterEach(function (): void {
    removeDirectory($this->tempDir);
});

it('writes to a generated file', function (): void {
    $logger = new Stream($this->tempDir);

    expect($logger->getLogFilePath())->toStartWith($this->tempDir);
    expect($logger->getLogFilePath())->toContain('log_');
    expect($logger->getLogFilePath())->toEndWith('.txt');

    $logger->log(LogLevel::INFO, 'hello world');

    $content = file_get_contents($logger->getLogFilePath());
    expect($content)->toContain('[info]');
    expect($content)->toContain('hello world');
    expect($logger->getLastLogLine())->toContain('hello world');
});

it('writes to an explicit file path', function (): void {
    $file   = $this->tempDir . '/custom.log';
    $logger = new Stream($file);

    expect($logger->getLogFilePath())->toBe($file);

    $logger->log(LogLevel::ERROR, 'explicit path');

    expect(file_get_contents($file))->toContain('explicit path');
});

it('writes to a php stream', function (): void {
    $logger = new Stream('php://stderr');

    expect($logger->getLogFilePath())->toBe('php://stderr');

    $logger->log(LogLevel::WARNING, 'to stderr');

    expect($logger->getLastLogLine())->toContain('to stderr');
});

it('respects the log level threshold', function (): void {
    $logger = new Stream($this->tempDir . '/threshold.log', LogLevel::ERROR);

    $logger->log(LogLevel::INFO, 'below threshold');

    expect($logger->getLastLogLine())->toBe('');

    $logger->log(LogLevel::ERROR, 'at threshold');

    expect($logger->getLastLogLine())->toContain('at threshold');
});

it('can set the log level threshold', function (): void {
    $logger = new Stream($this->tempDir . '/threshold.log');

    $logger->setLogLevelThreshold(LogLevel::CRITICAL);
    $logger->log(LogLevel::INFO, 'filtered');

    expect($logger->getLastLogLine())->toBe('');
});

it('throws an exception for an invalid log level', function (): void {
    $logger = new Stream($this->tempDir . '/invalid.log');

    $logger->log('invalid', 'message');
})->throws(LogArgumentException::class, 'Invalid log level: invalid');

it('throws an exception for a non-string log level', function (): void {
    $logger = new Stream($this->tempDir . '/invalid.log');

    $property = new ReflectionProperty(Stream::class, 'logLevels');
    $property->setAccessible(true);
    $property->setValue($logger, [0 => 0] + $property->getValue($logger));

    $logger->log(0, 'message');
})->throws(LogArgumentException::class, 'Log level must be a string, integer given.');

it('throws an exception when a write fails', function (): void {
    $logger = new Stream($this->tempDir . '/write-fail.log');

    $readonly = $this->tempDir . '/readonly.bin';
    touch($readonly);
    $handle = fopen($readonly, 'r');

    setProperty($logger, 'fileHandle', $handle);

    set_error_handler(static fn (): bool => true);

    try {
        @$logger->write('boom');
    } finally {
        restore_error_handler();
        fclose($handle);
    }
})->throws(RuntimeException::class);

it('does nothing when writing with a null handle', function (): void {
    $logger = new Stream($this->tempDir . '/null-handle.log');

    setProperty($logger, 'fileHandle', null);

    $logger->write('ignored');

    expect($logger->getLastLogLine())->toBe('');
});

it('writes when the handle is a non-resource', function (): void {
    $logger = new Stream($this->tempDir . '/non-resource.log', LogLevel::DEBUG, [
        'flushFrequency' => 1,
    ]);

    setProperty($logger, 'fileHandle', 'not-a-resource');

    $logger->write('  hello  ');

    expect($logger->getLastLogLine())->toBe('hello');
});

it('writes when the handle is a non-resource without a flush frequency', function (): void {
    $logger = new Stream($this->tempDir . '/non-resource.log');

    setProperty($logger, 'fileHandle', 'not-a-resource');

    $logger->write('  hello  ');

    expect($logger->getLastLogLine())->toBe('hello');
});

it('writes when a flush is not triggered', function (): void {
    $logger = new Stream($this->tempDir . '/non-resource.log', LogLevel::DEBUG, [
        'flushFrequency' => 2,
    ]);

    setProperty($logger, 'fileHandle', 'not-a-resource');

    $logger->write('first');

    expect($logger->getLastLogLine())->toBe('first');
});

it('flushes according to the flush frequency', function (): void {
    $logger = new Stream($this->tempDir . '/flush.log', LogLevel::DEBUG, [
        'flushFrequency' => 2,
    ]);

    $logger->write('one');

    expect($logger->getLastLogLine())->toBe('one');

    $logger->write('two');

    expect($logger->getLastLogLine())->toBe('two');
});

it('can set the date format', function (): void {
    $logger = new Stream($this->tempDir . '/date-format.log');

    $logger->setDateFormat('Y-m-d');

    expect(true)->toBeTrue();
});

it('throws an exception when the file handle cannot be set', function (): void {
    $logger = new Stream($this->tempDir . '/handle.log');

    setProperty($logger, 'logFilePath', $this->tempDir);

    @$logger->setFileHandle('a');
})->throws(RuntimeException::class);

it('fails when the directory cannot be created', function (): void {
    $blocker = $this->tempDir . '/blocker.log';
    touch($blocker);

    @new Stream($blocker . '/subdir');
})->throws(RuntimeException::class);

it('fails when the file is not writable', function (): void {
    $file = $this->tempDir . '/locked.log';
    touch($file);
    chmod($file, 0o444);

    try {
        new Stream($file);
    } finally {
        chmod($file, 0o644);
    }
})->throws(RuntimeException::class);

it('fails when a generated file is not writable', function (): void {
    $file = $this->tempDir . '/locked.log';
    touch($file);
    chmod($file, 0o444);

    try {
        new Stream($this->tempDir, LogLevel::DEBUG, [
            'filename' => 'locked.log',
        ]);
    } finally {
        chmod($file, 0o644);
    }
})->throws(RuntimeException::class);

it('creates a nested directory for a generated file', function (): void {
    $logger = new Stream($this->tempDir . '/nested/logs');

    expect(is_dir($this->tempDir . '/nested/logs'))->toBeTrue();
    expect($logger->getLogFilePath())->toStartWith($this->tempDir . '/nested/logs');
});

it('creates a nested directory for an explicit file', function (): void {
    $logger = new Stream($this->tempDir . '/nested/app.log');

    expect(is_dir($this->tempDir . '/nested'))->toBeTrue();
    expect($logger->getLogFilePath())->toBe($this->tempDir . '/nested/app.log');
});

it('opens an existing writable explicit file', function (): void {
    $file = $this->tempDir . '/existing.log';
    touch($file);

    $logger = new Stream($file);

    $logger->log(LogLevel::INFO, 'appended');

    expect(file_get_contents($file))->toContain('appended');
});

it('opens an existing writable generated file', function (): void {
    $file = $this->tempDir . '/existing.log';
    touch($file);

    $logger = new Stream($this->tempDir, LogLevel::DEBUG, [
        'filename' => 'existing.log',
    ]);

    $logger->log(LogLevel::INFO, 'appended');

    expect(file_get_contents($file))->toContain('appended');
});

it('fails when the directory cannot be created for an explicit file', function (): void {
    $blocker = $this->tempDir . '/blocker.log';
    touch($blocker);

    @new Stream($blocker . '/sub.log');
})->throws(RuntimeException::class);

it('sets the log file path with a filename', function (): void {
    $logger = new Stream($this->tempDir, LogLevel::DEBUG, [
        'filename' => 'app.log',
    ]);

    expect($logger->getLogFilePath())->toEndWith('app.log');
});

it('sets the log file path with a filename without extension', function (): void {
    $logger = new Stream($this->tempDir, LogLevel::DEBUG, [
        'filename'  => 'app',
        'extension' => 'log',
    ]);

    expect($logger->getLogFilePath())->toEndWith('app.log');
});

it('sets the log file path with a prefix', function (): void {
    $logger = new Stream($this->tempDir, LogLevel::DEBUG, [
        'prefix' => 'custom_',
    ]);

    expect($logger->getLogFilePath())->toContain('custom_');
    expect($logger->getLogFilePath())->toEndWith('.txt');
});

it('sets the log file path with a txt filename', function (): void {
    $logger = new Stream($this->tempDir, LogLevel::DEBUG, [
        'filename' => 'app.txt',
    ]);

    expect($logger->getLogFilePath())->toEndWith('app.txt');
});

it('falls back to the extension for a non-string filename', function (): void {
    $logger = new Stream($this->tempDir, LogLevel::DEBUG, [
        'filename' => true,
    ]);

    expect($logger->getLogFilePath())->toEndWith('.txt');
});

it('appends context to the log', function (): void {
    $logger = new Stream($this->tempDir . '/context.log');

    $logger->log(LogLevel::INFO, 'hello', ['user' => 'omega']);

    $content = file_get_contents($logger->getLogFilePath());
    expect($content)->toContain('hello');
    expect($content)->toContain('user:');
});

it('does not append context when disabled', function (): void {
    $logger = new Stream($this->tempDir . '/no-context.log', LogLevel::DEBUG, [
        'appendContext' => false,
    ]);

    $logger->log(LogLevel::INFO, 'hello', ['user' => 'omega']);

    $content = file_get_contents($logger->getLogFilePath());
    expect($content)->toContain('hello');
    expect($content)->not->toContain('user:');
});

it('uses a custom log format', function (): void {
    $logger = new Stream($this->tempDir . '/format.log', LogLevel::DEBUG, [
        'logFormat'     => '{date} | {level} | {level-padding} | {message} | {context}',
        'appendContext' => false,
    ]);

    $logger->log(LogLevel::INFO, 'formatted', ['x' => 1]);

    $content = file_get_contents($logger->getLogFilePath());
    expect($content)->toContain('| INFO |');
    expect($content)->toContain('formatted');
    expect($content)->toContain('{"x":1}');
    expect($content)->not->toContain('{date}');
});

it('uses a custom log format with an appended context', function (): void {
    $logger = new Stream($this->tempDir . '/format.log', LogLevel::DEBUG, [
        'logFormat' => '{level} | {message}',
    ]);

    $logger->log(LogLevel::WARNING, 'with context', ['user' => 'omega']);

    $content = file_get_contents($logger->getLogFilePath());
    expect($content)->toContain('WARNING | with context');
    expect($content)->toContain('user:');
});

it('falls back to the default format for an empty string log format', function (): void {
    $logger = new Stream($this->tempDir . '/empty-format.log', LogLevel::DEBUG, [
        'logFormat' => '',
    ]);

    $logger->log(LogLevel::INFO, 'default format', ['user' => 'omega']);

    $content = file_get_contents($logger->getLogFilePath());
    expect($content)->toContain('[info]');
    expect($content)->toContain('default format');
    expect($content)->toContain('user:');
});

it('falls back to the default format without appending context', function (): void {
    $logger = new Stream($this->tempDir . '/empty-format.log', LogLevel::DEBUG, [
        'logFormat'     => '',
        'appendContext' => false,
    ]);

    $logger->log(LogLevel::INFO, 'default format', ['user' => 'omega']);

    $content = file_get_contents($logger->getLogFilePath());
    expect($content)->toContain('[info]');
    expect($content)->not->toContain('user:');
});

it('logs a stringable message with a custom log format', function (): void {
    $logger = new Stream($this->tempDir . '/stringable-format.log', LogLevel::DEBUG, [
        'logFormat' => '{message}',
    ]);

    $logger->log(LogLevel::INFO, new StringableMessage('stringable formatted'), ['user' => 'omega']);

    expect(file_get_contents($logger->getLogFilePath()))->toContain('stringable formatted');
});

it('logs a stringable message without append context', function (): void {
    $logger = new Stream($this->tempDir . '/stringable.log', LogLevel::DEBUG, [
        'appendContext' => false,
    ]);

    $logger->log(LogLevel::INFO, new StringableMessage('stringable bare'), ['user' => 'omega']);

    $content = file_get_contents($logger->getLogFilePath());
    expect($content)->toContain('stringable bare');
    expect($content)->not->toContain('user:');
});

it('logs a stringable message', function (): void {
    $logger = new Stream($this->tempDir . '/stringable.log');

    $logger->log(LogLevel::INFO, new StringableMessage('stringable message'));

    expect(file_get_contents($logger->getLogFilePath()))->toContain('stringable message');
});

it('logs a stringable message with a custom format without context', function (): void {
    $logger = new Stream($this->tempDir . '/stringable-format.log', LogLevel::DEBUG, [
        'logFormat' => '{message}',
    ]);

    $logger->log(LogLevel::INFO, new StringableMessage('stringable formatted'));

    expect(file_get_contents($logger->getLogFilePath()))->toContain('stringable formatted');
});

it('logs a stringable message with a custom format without append context', function (): void {
    $logger = new Stream($this->tempDir . '/stringable-format.log', LogLevel::DEBUG, [
        'logFormat'     => '{message}',
        'appendContext' => false,
    ]);

    $logger->log(LogLevel::INFO, new StringableMessage('stringable formatted'), ['x' => 1]);

    $content = file_get_contents($logger->getLogFilePath());
    expect($content)->toContain('stringable formatted');
    expect($content)->not->toContain('x:');
});

it('logs a stringable message with an empty log format and context', function (): void {
    $logger = new Stream($this->tempDir . '/stringable-format.log', LogLevel::DEBUG, [
        'logFormat' => '',
    ]);

    $logger->log(LogLevel::INFO, new StringableMessage('stringable formatted'), ['user' => 'omega']);

    $content = file_get_contents($logger->getLogFilePath());
    expect($content)->toContain('stringable formatted');
    expect($content)->toContain('user:');
});

it('logs a stringable message with an empty log format without context', function (): void {
    $logger = new Stream($this->tempDir . '/stringable-format.log', LogLevel::DEBUG, [
        'logFormat' => '',
    ]);

    $logger->log(LogLevel::INFO, new StringableMessage('stringable formatted'));

    expect(file_get_contents($logger->getLogFilePath()))->toContain('stringable formatted');
});

it('logs a stringable message with an empty log format without append context', function (): void {
    $logger = new Stream($this->tempDir . '/stringable-format.log', LogLevel::DEBUG, [
        'logFormat'     => '',
        'appendContext' => false,
    ]);

    $logger->log(LogLevel::INFO, new StringableMessage('stringable formatted'), ['x' => 1]);

    $content = file_get_contents($logger->getLogFilePath());
    expect($content)->toContain('stringable formatted');
    expect($content)->not->toContain('x:');
});

it('logs a stringable message with a non-string log format', function (): void {
    $logger = new Stream($this->tempDir . '/stringable-format.log', LogLevel::DEBUG, [
        'logFormat' => 123,
    ]);

    $logger->log(LogLevel::INFO, new StringableMessage('stringable formatted'), ['user' => 'omega']);

    $content = file_get_contents($logger->getLogFilePath());
    expect($content)->toContain('stringable formatted');
    expect($content)->toContain('user:');
});

it('uses a custom log format without context', function (): void {
    $logger = new Stream($this->tempDir . '/format.log', LogLevel::DEBUG, [
        'logFormat' => '{level} | {message}',
    ]);

    $logger->log(LogLevel::INFO, 'formatted');

    $content = file_get_contents($logger->getLogFilePath());
    expect($content)->toContain('INFO | formatted');
    expect($content)->not->toContain('{message}');
});

it('uses an empty log format without context', function (): void {
    $logger = new Stream($this->tempDir . '/empty-format.log', LogLevel::DEBUG, [
        'logFormat' => '',
    ]);

    $logger->log(LogLevel::INFO, 'default format');

    $content = file_get_contents($logger->getLogFilePath());
    expect($content)->toContain('[info]');
    expect($content)->toContain('default format');
});

it('falls back to the default date format for a non-string date format', function (): void {
    $logger = new Stream($this->tempDir . '/fallback.log', LogLevel::DEBUG, [
        'dateFormat' => 123,
    ]);

    $logger->log(LogLevel::INFO, 'fallback format');

    expect(file_get_contents($logger->getLogFilePath()))->toContain('fallback format');
});

it('handles a non-resource handle on destruction', function (): void {
    $logger = new Stream($this->tempDir . '/destruct.log');

    setProperty($logger, 'fileHandle', 'foo');
    $logger = null;

    expect(true)->toBeTrue();
});

function setProperty(object $object, string $property, mixed $value): void
{
    $reflection = new ReflectionProperty($object, $property);
    $reflection->setAccessible(true);
    $reflection->setValue($object, $value);
}

function removeDirectory(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }

    array_map(
        function (string $entry) use ($dir): void {
            $path = $dir . slash(path: '/') . $entry;

            if (is_dir($path)) {
                removeDirectory($path);
            } else {
                @unlink($path);
            }
        },
        array_diff(scandir($dir), ['.', '..'])
    );

    @rmdir($dir);
}
