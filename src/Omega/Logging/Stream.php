<?php

/**
 * Part of Omega - Logging Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Logging;

use DateMalformedStringException;
use Omega\Logging\Exception\LogArgumentException;
use Omega\Time\Now;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use RuntimeException;
use Stringable;

use function array_keys;
use function array_merge;
use function array_reduce;
use function date;
use function dirname;
use function fclose;
use function file_exists;
use function fflush;
use function fopen;
use function fwrite;
use function is_resource;
use function is_writable;
use function json_encode;
use function mkdir;
use function preg_replace;
use function rtrim;
use function strlen;
use function str_contains;
use function str_repeat;
use function str_replace;
use function str_ends_with;
use function str_starts_with;
use function strtolower;
use function strtoupper;
use function trim;
use function var_export;
use function Omega\Application\slash;

/**
 * Class Stream.
 *
 * The stream driver writes log messages to a file (or a PHP stream such as
 * `php://stdout` or `php://stderr`). It manages log levels, formats messages,
 * and handles log contexts. It supports various log configurations such as
 * log format, date format, log file path, and context management.
 *
 * @category   Omega
 * @package    Logging
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class Stream extends AbstractLogger
{
    /**
     * Log array options.
     *
     * @var array<string, mixed> Contains various configuration options for the logger, such as:
     *                           - 'extension': The file extension for log files (default: 'txt').
     *                           - 'dateFormat': The format for timestamps in the logs (default: 'Y-m-d G:i:s.u').
     *                           - 'filename': Custom filename for the log file (default: false).
     *                           - 'flushFrequency': The number of lines before flushing the output buffer
     *                              (default: false).
     *                           - 'prefix': The prefix for the log file (default: 'log_').
     *                           - 'logFormat': The format string for log entries (default: false).
     *                           - 'appendContext': Whether to append context information to log messages
     *                              (default: true).
     */
    protected array $options = [
        'extension'      => 'txt',
        'dateFormat'     => 'Y-m-d G:i:s.u',
        'filename'       => false,
        'flushFrequency' => false,
        'prefix'         => 'log_',
        'logFormat'      => false,
        'appendContext'  => true,
    ];

    /*°
     * Path to the log file.
     *
     * @var string Holds the path to the log file.
     */
    private string $logFilePath;

    /**
     * Threshold for log levels.
     *
     * @var string The minimum log level for which messages will be logged (default: LogLevel::DEBUG).
     */
    protected string $logLevelThreshold = LogLevel::DEBUG;

    /**
     * The number of log lines written so far.
     *
     * @var int Holds the number of log lines written so far.
     */
    private int $logLineCount = 0;

    /**
     * Available log levels and their priorities.
     *
     * @var array<string, int> Maps log levels (e.g., 'EMERGENCY', 'DEBUG') to their numeric priorities.
     */
    protected array $logLevels = [
        LogLevel::EMERGENCY => 0,
        LogLevel::ALERT     => 1,
        LogLevel::CRITICAL  => 2,
        LogLevel::ERROR     => 3,
        LogLevel::WARNING   => 4,
        LogLevel::NOTICE    => 5,
        LogLevel::INFO      => 6,
        LogLevel::DEBUG     => 7,
    ];

    /**
     * The file handle for writing log messages.
     *
     * @var resource|null Holds the file handle for writing log message.
     */
    private mixed $fileHandle = null;

    /**
     * The last line written to the log.
     *
     * @var string Holds the last line written to the log.
     */
    private string $lastLine = '';

    /**
     * Default permissions for newly created log directories.
     *
     * @var int Holds the default permission for newly created log directories.
     */
    private int $defaultPermissions = 0777;

    /**
     * Constructor.
     *
     * Initializes the logger by setting up the log directory, log level, and file options.
     *
     * @param string               $logDirectory      The directory where log files are stored, or an
     *                                                explicit log file path ending in `.log`/`.txt`.
     * @param string               $logLevelThreshold The log level threshold (default: LogLevel::DEBUG).
     * @param array<string, mixed> $options           Optional configurations for the logger.
     * @return void
     * @throws RuntimeException If the log file cannot be created or opened.
     */
    public function __construct(
        string $logDirectory,
        string $logLevelThreshold = LogLevel::DEBUG,
        array $options = []
    ) {
        $this->logLevelThreshold = $logLevelThreshold;
        $this->options           = array_merge($this->options, $options);

        $logDirectory = rtrim($logDirectory, slash(path: '/'));

        if (str_starts_with($logDirectory, 'php://')) {
            $this->setLogToStdOut($logDirectory);
            $this->setFileHandle('w+');

            return;
        }

        $directory = $this->isLogFilePath($logDirectory)
            ? dirname($logDirectory)
            : $logDirectory;

        if (!file_exists($directory)) {
            if (!mkdir($directory, $this->defaultPermissions, true) && !is_dir($directory)) {
                throw new RuntimeException(
                    'Unable to create log directory: ' . $directory
                );
            }
        }

        $this->setLogFilePath($logDirectory);

        if (file_exists($this->logFilePath) && !is_writable($this->logFilePath)) {
            throw new RuntimeException(
                'The file could not be written to. Check that appropriate permissions have been set.'
            );
        }

        $this->setFileHandle('a');
    }

    /**
     * Sets the log file to use stdout for logging.
     *
     * @param string $stdOutPath The stdout path for logging.
     * @return void
     */
    public function setLogToStdOut(string $stdOutPath): void
    {
        $this->logFilePath = $stdOutPath;
    }

    /**
     * Sets the path to the log file.
     *
     * @param string $logDirectory The directory where the log file will be stored.     * @return void
     */
    public function setLogFilePath(string $logDirectory): void
    {
        if ($this->options['filename']) {
            if (
                is_string($this->options['filename'])
                && (str_contains($this->options['filename'], '.log')
                || str_contains($this->options['filename'], '.txt'))
            ) {
                $this->logFilePath = $logDirectory . slash(path: '/') . $this->options['filename'];
            } else {
                $this->logFilePath = $logDirectory
                    . slash(path: '/')
                    . $this->options['filename']
                    . '.'
                    . $this->options['extension'];
            }
        } elseif ($this->isLogFilePath($logDirectory)) {
            $this->logFilePath = $logDirectory;
        } else {
            $this->logFilePath = $logDirectory
                    . slash(path: '/')
                    . $this->options['prefix']
                    . date('Y-m-d')
                    . '.'
                    . $this->options['extension'];
        }
    }

    /**
     * Determine whether the given path is an explicit log file path.
     *
     * A path is considered an explicit log file when it ends with a `.log`
     * or `.txt` extension. In that case the logger writes directly to that
     * file instead of generating a filename inside the directory.
     *
     * @param string $path The path to inspect.
     * @return bool True when the path points to a log file.
     */
    private function isLogFilePath(string $path): bool
    {
        $path = strtolower($path);

        return str_ends_with($path, '.log') || str_ends_with($path, '.txt');
    }

    /**
     * Opens the log file for writing.
     *
     * @param string $writeMode The mode in which to open the log file (e.g., 'a' for append).
     * @return void
     * @throws RuntimeException If the log file cannot be opened.
     */
    public function setFileHandle(string $writeMode): void
    {
        $handle = fopen($this->logFilePath, $writeMode);

        if ($handle === false) {
            throw new RuntimeException(
                'Failed to open log file for writing.'
            );
        }

        $this->fileHandle = $handle;
    }

    /**
     * Sets the date format for log entries.
     *
     * @param string $dateFormat The date format to use.
     * @return void
     */
    public function setDateFormat(string $dateFormat): void
    {
        $this->options['dateFormat'] = $dateFormat;
    }

    /**
     * Sets the log level threshold.
     *
     * @param string $logLevelThreshold The log level threshold to set.
     * @return void
     */
    public function setLogLevelThreshold(string $logLevelThreshold): void
    {
        $this->logLevelThreshold = $logLevelThreshold;
    }

    /**
     * Writes a log message to the log file.
     *
     * @param string $message The message to log.
     * @return void
     * @throws RuntimeException If the file cannot be written to.
     */
    public function write(string $message): void
    {
        if (null !== $this->fileHandle) {
            if (is_resource($this->fileHandle) && fwrite($this->fileHandle, $message) === false) {
                throw new RuntimeException(
                    'The file could not be written to. Check that appropriate permissions have been set.'
                );
            } else {
                $this->lastLine = trim($message);
                ++$this->logLineCount;

                if ($this->options['flushFrequency'] && $this->logLineCount % $this->options['flushFrequency'] === 0) {
                    if (is_resource($this->fileHandle)) {
                        fflush($this->fileHandle);
                    }
                }
            }
        }
    }

    /**
     * Get the file path that the log is currently writing to.
     *
     * @return string Return the pth of the log file.
     */
    public function getLogFilePath(): string
    {
        return $this->logFilePath;
    }

    /**
     * Get the last line logged to the log file.
     *
     * @return string Returns the last line logged to the file.
     */
    public function getLastLogLine(): string
    {
        return $this->lastLine;
    }

    /**
     * Formats a log message for writing to the file.
     *
     * @param string $level The log level of the message.
     * @param string|Stringable $message The message to log.
     * @param array<string, mixed> $context The context for the log message.
     * @return string The formatted message.
     * @throws DateMalformedStringException
     */
    protected function formatMessage(string $level, string|Stringable $message, array $context): string
    {
        if ($message instanceof Stringable) {
            $message = $message->__toString();
        }

        $logFormat = is_string($this->options['logFormat']) ? $this->options['logFormat'] : '';

        if ($logFormat !== '') {
            $parts = [
                'date'          => $this->getTimestamp(),
                'level'         => strtoupper($level),
                'level-padding' => str_repeat(' ', 9 - strlen($level)),
                'priority'      => $this->logLevels[$level],
                'message'       => $message,
                'context'       => json_encode($context),
            ];

            $formattedMessage = array_reduce(
                array_keys($parts),
                static function (string $carry, string $part) use ($parts): string {
                    $value = $parts[$part];

                    return is_string($value)
                        ? str_replace('{' . $part . '}', $value, $carry)
                        : $carry;
                },
                $logFormat
            );
        } else {
            $formattedMessage = '[' . $this->getTimestamp() . '] [' . $level . '] ' . $message;
        }

        if ($this->options['appendContext'] && !empty($context)) {
            $formattedMessage .= PHP_EOL . $this->indent($this->contextToString($context));
        }

        return $formattedMessage . PHP_EOL;
    }

    /**
     * Gets the correctly formatted Date/Time for the log entry.
     *
     * @return string Return the formatted timestamp.
     * @throws DateMalformedStringException
     */
    private function getTimestamp(): string
    {
        $now    = new Now();
        $format = is_string($this->options['dateFormat'])
            ? $this->options['dateFormat']
            : 'Y-m-d H:i:s.u';

        return $now->format($format);
    }

    /**
     * Converts the given context array to a string.
     *
     * @param array<string, mixed> $context The context data.
     * @return string The context as a string.
     */
    protected function contextToString(array $context): string
    {
        $export = array_reduce(
            array_keys($context),
            function (string $carry, string $key) use ($context): string {
                $carry .= $key . ': ';
                $carry .= preg_replace(
                    [
                        '/=>\s+([a-zA-Z])/im',
                        '/array\(\s+\)/im',
                        //'/^  |\G  /m'
                        '/^\s{2}|\G\s{2}/m',
                    ],
                    [
                        '=> $1',
                        '[]',
                        '    ',
                    ],
                    str_replace('array (', 'array(', var_export($context[$key], true))
                );
                $carry .= PHP_EOL;

                return $carry;
            },
            ''
        );

        return str_replace(['\\\\', '\\\''], ['\\', '\''], rtrim($export));
    }

    /**
     * Indents the given string with the given indent.
     *
     * @param string $string The string to indent
     * @param string $indent What to use as the indent.
     * @return string Return the given string with the given indent.
     */
    protected function indent(string $string, string $indent = '    '): string
    {
        return $indent . str_replace("\n", "\n" . $indent, $string);
    }

    /**
     * Logs a message with a given level.
     *
     * @param mixed                $level   The log level.
     * @param string|Stringable    $message The message to log.
     * @param array<string, mixed> $context The context for the log message.
     * @return void
     * @throws LogArgumentException
     * @throws DateMalformedStringException
     */
    public function log(mixed $level, string|Stringable $message, array $context = []): void
    {
        if (!isset($this->logLevels[$level])) {
            throw new LogArgumentException(
                'Invalid log level: '
                . $level
            );
        }

        if ($this->logLevels[$this->logLevelThreshold] < $this->logLevels[$level]) {
            return;
        }

        if (!is_string($level)) {
            throw new LogArgumentException('Log level must be a string, ' . gettype($level) . ' given.');
        }

        $message = $this->formatMessage($level, $message, $context);
        $this->write($message);
    }

    /**
     * Destructor.
     *
     * Closes the file handle when the logger is destroyed.
     *
     * @return void
     */
    public function __destruct()
    {
        if (is_resource($this->fileHandle)) {
            fclose($this->fileHandle);
        }
    }
}
