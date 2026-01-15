<?php

/**
 * Part of Omega - Console Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Console;

use ArrayAccess;
use InvalidArgumentException;
use Omega\Console\Exceptions\ImmutableOptionException;
use Omega\Console\IO\OutputStream;
use Omega\Console\Style\Style;
use Omega\Console\Traits\TerminalTrait;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use ReturnTypeWillChange;

use function array_key_exists;
use function array_merge;
use function array_shift;
use function count;
use function explode;
use function is_array;
use function is_int;
use function preg_match;
use function preg_replace;
use function str_split;

/**
 * AbstractCommand
 *
 * This abstract class provides a base implementation for console commands,
 * handling parsing of command line arguments, options, and their mappings.
 * It implements ArrayAccess to allow array-like access to options and
 * implements CommandInterface to define a standard `main` method.
 *
 * It also uses TerminalTrait for console output utilities.
 *
 * @category  Omega
 * @package   Console
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 *
 * @implements ArrayAccess<string, string|bool|int|null>
 *
 * @property string|int|bool|null $name
 * @property string|int|bool|null $nick
 * @property string|int|bool|null $whois
 * @property string|int|bool|null $default
 * @property string|int|bool|null $_
 * @property string|int|bool|null $t
 * @property string|int|bool|null $n
 * @property string|int|bool|null $config
 * @property string|int|bool|null $s
 * @property string|int|bool|null $l
 * @property string|int|bool|null $cp
 * @property string|int|bool|null $io
 * @property string|int|bool|null $i
 * @property string|int|bool|null $o
 * @property string|int|bool|null $ab
 * @property string|int|bool|null $a
 * @property string|int|bool|null $b
 * @property string|int|bool|null $y
 * @property string|int|bool|null $d
 * @property array                $vvv
 * @property string|int|bool|null $v
 *
 * @method echoTextGreen()
 * @method echoTextYellow()
 * @method echoTextRed()
 */
abstract class AbstractCommand implements ArrayAccess, CommandInterface
{
    use TerminalTrait;

    /** @var int Status code representing a successful command execution. */
    public const int SUCCESS = 0;

    /** @var int Status code representing a failed command execution. */
    public const int FAILURE = 1;

    /** @var int Status code representing an invalid command or input. */
    public const int INVALID = 2;

    /** @var int Silent verbosity level, no output will be shown. */
    public const int VERBOSITY_SILENT = 0;

    /** @var int Quiet verbosity level, minimal output displayed. */
    public const int VERBOSITY_QUIET = 1;

    /** @var int Normal verbosity level, default for regular output. */
    public const int VERBOSITY_NORMAL = 2;

    /** @var int Verbose verbosity level, more detailed output. */
    public const int VERBOSITY_VERBOSE = 3;

    /** @var int Very verbose verbosity level, extensive output shown. */
    public const int VERBOSITY_VERY_VERBOSE = 4;

    /** @var int Debug verbosity level, maximum detail including debug info. */
    public const int VERBOSITY_DEBUG = 5;

    /** @var int Default verbosity level. */
    protected int $verbosity = self::VERBOSITY_NORMAL;

    /** @var string|array<int, string> Commandline line input from $argv. */
    protected string|array $cmd;

    /** @var array<int, string> Parsed command line options. */
    protected array $option;

    /** @var array<string, string|string[]|bool|int|null> Option mapper associating option names to values */
    protected array $optionMapper;

    /** @var array<string, string> Descriptions of command options for input. */
    protected array $commandDescribes = [];

    /** @var array<string, string> Descriptions of command options for printing. */
    protected array $optionDescribes = [];

    /** @var array<string, array<int, string>> Relations between options and arguments. */
    protected array $commandRelation = [];

    /** @var OutputStream Holds the output stream object. */
    protected OutputStream $outputStream;

    /**
     * Parse command line arguments and initialize options.
     *
     * @param array<int, string>                  $argv          Array of command line arguments.
     * @param array<string, string|bool|int|null> $defaultOption Default option values to merge.
     */
    public function __construct(array $argv, array $defaultOption = [])
    {
        array_shift($argv);

        $this->cmd          = array_shift($argv) ?? '';
        $this->option       = $argv;
        $this->optionMapper = $defaultOption;

        foreach ($this->optionMapper($argv) as $key => $value) {
            $this->optionMapper[$key] = $value;
        }

        $this->verbosity = $this->getDefaultVerbosity();
    }

    /**
     * Convert raw command line arguments into an associative array.
     *
     * @param array<int, string|bool|int|null> $argv Arguments to parse
     * @return array<string, string|bool|int|null> Parsed options
     */
    private function optionMapper(array $argv): array
    {
        $options      = [];
        $options['_'] = $options['name'] = $argv[0] ?? '';
        $lastOption   = null;
        $alias        = [];

        foreach ($argv as $key => $option) {
            if ($this->isCommandParam($option)) {
                $keyValue = explode('=', $option);
                $name     = preg_replace('/^(-{1,2})/', '', $keyValue[0]);

                // alias check
                /** @noinspection PhpUnusedLocalVariableInspection */
                if (preg_match('/^-(?!-)([a-zA-Z]+)$/', $keyValue[0], $singleDash)) {
                    $alias[$name] = array_key_exists($name, $alias)
                        ? array_merge($alias[$name], str_split($name))
                        : str_split($name);
                }

                // param have value
                if (isset($keyValue[1])) {
                    $options[$name] = $this->removeQuote($keyValue[1]);
                    continue;
                }

                // check value in next param
                $nextKey = $key + 1;

                if (!isset($argv[$nextKey])) {
                    $options[$name] = true;
                    continue;
                }

                $next           = $argv[$nextKey];
                if ($this->isCommandParam($next)) {
                    $options[$name] = true;
                }

                $lastOption = $name;
                continue;
            }

            if (null !== $lastOption) {
                if (false === isset($options[$lastOption])) {
                    $options[$lastOption] = [];
                } elseif (false === is_array($options[$lastOption])) {
                    $options[$lastOption] = [$options[$lastOption]];
                }

                $options[$lastOption][] = $this->removeQuote($option);
            } else {
                if (false === isset($options[''])) {
                    $options[''] = [];
                }

                $options[''][] = $this->removeQuote($option);
            }
        }

        // re-group alias
        foreach ($alias as $key => $names) {
            foreach ($names as $name) {
                if (array_key_exists($name, $options)) {
                    if (is_int($options[$name])) {
                        $options[$name]++;
                    }
                    continue;
                }
                $options[$name] = $options[$key];
            }
        }

        return $options;
    }

    /**
     * Check whether a string represents a command parameter (starts with '-' or '--')
     *
     * @param string $command Command string to check
     * @return bool True if it is a command parameter, false otherwise
     */
    private function isCommandParam(string $command): bool
    {
        return str_starts_with($command, '-');
    }

    /**
     * Remove surrounding quotes (single or double) from a string.
     *
     * @param string $value Value to strip quotes from
     * @return string Unquoted value
     */
    private function removeQuote(string $value): string
    {
        $len = strlen($value);

        if ($len < 2) {
            return $value;
        }

        $first = $value[0];
        $last  = $value[$len - 1];

        // Only remove matching quotes at both ends
        if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
            return substr($value, 1, -1);
        }

        return $value;
    }

    /**
     * Get the value of a parsed command option by name.
     *
     * @param string $name    Option name
     * @param string|int|bool|array|null $default Default value if option is not present
     * @return string|int|bool|array|null Option value or default
     */
    protected function option(string $name, array|bool|int|string|null $default = null): array|bool|int|string|null
    {
        if (!array_key_exists($name, $this->optionMapper)) {
            return $default;
        }
        $option = $this->optionMapper[$name];
        if (is_array($option) && 1 === count($option)) {
            return $option[0];
        }

        return $option;
    }

    /**
     * Ensure that the given directory exists. Creates it recursively if missing.
     *
     * @param string $binding Logical path or container binding (e.g., "app.Http.Middlewares")
     * @return string Absolute filesystem path
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    protected function isPath(string $binding): string
    {
        $logicalPath = get_path($binding);

        $realPath = str_replace(['.', '/','\\'], DIRECTORY_SEPARATOR, $logicalPath);

        if (!is_dir($realPath)) {
            mkdir($realPath, 0755, true);
        }

        return $realPath;
    }

    /**
     * Get exist option status.
     *
     * @param string $name Holds the option name.
     * @return bool True if option exists, false otherwise
     */
    protected function hasOption(string $name): bool
    {
        return array_key_exists($name, $this->optionMapper);
    }

    /**
     * Returns positional command-line arguments not associated with any option.
     *
     * @return string[] List of positional arguments.
     */
    protected function optionPosition(): array
    {
        return $this->optionMapper[''];
    }

    /**
     * Create and configure a Style output helper bound to the given output stream.
     *
     * @param OutputStream $outputStream Output stream used to write command output.
     * @param array{
     *     colorize?: bool,
     *     decorate?: bool,
     * } $options Optional output configuration overrides.
     * @return Style Configured style instance for formatted output.
     */
    protected function output(OutputStream $outputStream, array $options = []): Style
    {
        /** @noinspection PhpParamsInspection */
        $output = new Style(options: [
            'colorize' => $options['colorize'] ?? $this->hasColorSupport(),
            'decorate' => $options['decorate'] ?? null,
        ]);
        $output->setOutputStream($outputStream);

        return $output;
    }

    /**
     * Inject default options without overwriting
     *
     * 1. quiet with flag --quite
     * 2. verbose with flag -v,-vv or -vvv
     * 3. debug with flag --debug
     *
     * if there is no default option set, then set default verbosity to normal.
     *
     * @return int One of the VERBOSITY_* constants.
     */
    protected function getDefaultVerbosity(): int
    {
        if ($this->hasOption('silent')) {
            return self::VERBOSITY_SILENT;
        }

        if ($this->hasOption('quiet')) {
            return self::VERBOSITY_QUIET;
        }

        if ($this->hasOption('debug') || $this->hasOption('vvv')) {
            return self::VERBOSITY_DEBUG;
        }

        if ($this->hasOption('very-verbose') || $this->hasOption('vv')) {
            return self::VERBOSITY_VERY_VERBOSE;
        }

        if ($this->hasOption('verbose') || $this->hasOption('v')) {
            return self::VERBOSITY_VERBOSE;
        }

        return self::VERBOSITY_NORMAL;
    }

    /**
     * Set the current verbosity level.
     *
     * @param int $verbosity Verbosity level, must be between VERBOSITY_SILENT and VERBOSITY_DEBUG.
     * @return void
     * @throws InvalidArgumentException If the verbosity level is out of range.
     */
    public function setVerbosity(int $verbosity): void
    {
        if ($verbosity < self::VERBOSITY_SILENT || $verbosity > self::VERBOSITY_DEBUG) {
            throw new InvalidArgumentException(
                'Verbosity level must be between ' . self::VERBOSITY_SILENT . ' and ' . self::VERBOSITY_DEBUG
            );
        }

        $this->verbosity = $verbosity;
    }

    /**
     * Get the current verbosity level.
     *
     * @return int Current verbosity level.
     */
    public function getVerbosity(): int
    {
        return $this->verbosity;
    }

    /**
     * Determine whether the command is running in silent mode.
     *
     * @return bool True if verbosity is VERBOSITY_SILENT.
     */
    public function isSilent(): bool
    {
        return $this->verbosity === self::VERBOSITY_SILENT;
    }

    /**
     * Determine whether the command is running in quiet mode.
     *
     * @return bool True if verbosity is VERBOSITY_QUIET.
     */
    public function isQuiet(): bool
    {
        return $this->verbosity === self::VERBOSITY_QUIET;
    }

    /**
     * Determine whether the command is running in verbose mode or higher.
     *
     * @return bool True if verbosity is VERBOSITY_VERBOSE or above.
     */
    public function isVerbose(): bool
    {
        return $this->verbosity >= self::VERBOSITY_VERBOSE;
    }

    /**
     * Determine whether the command is running in very verbose mode or higher.
     *
     * @return bool True if verbosity is VERBOSITY_VERY_VERBOSE or above.
     */
    public function isVeryVerbose(): bool
    {
        return $this->verbosity >= self::VERBOSITY_VERY_VERBOSE;
    }

    /**
     * Determine whether the command is running in debug mode.
     *
     * @return bool True if verbosity is VERBOSITY_DEBUG.
     */
    public function isDebug(): bool
    {
        return $this->verbosity >= self::VERBOSITY_DEBUG;
    }

    /**
     * Magic getter to access option values as properties.
     *
     * @param string $name Option name
     * @return array|string|bool|int|null Option value or null if not set
     */
    public function __get(string $name): array|string|bool|int|null
    {
        return $this->option($name);
    }

    /**
     * ArrayAccess: Check if an option exists.
     *
     * @param mixed $offset Option name
     * @return bool True if option exists
     */
    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->optionMapper);
    }

    /**
     * ArrayAccess: Get the value of an option.
     *
     * @param mixed $offset Option name
     * @return string|int|bool|array|null Option value
     */
    #[ReturnTypeWillChange]
    public function offsetGet(mixed $offset): string|int|bool|array|null
    {
        return $this->option($offset);
    }

    /**
     * ArrayAccess: Prevent modification of options.
     *
     * @param mixed $offset Option name
     * @param mixed $value  Value
     * @return void
     * @throws ImmutableOptionException Always throws because options cannot be modified
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new ImmutableOptionException('Command cant be modify');
    }

    /**
     * ArrayAccess: Prevent unsetting of options.
     *
     * @param mixed $offset Option name
     * @return void
     * @throws ImmutableOptionException Always throws because options cannot be modified
     */
    public function offsetUnset(mixed $offset): void
    {
        throw new ImmutableOptionException('Command cant be modify');
    }

    /**
     * {@inheritdoc}
     */
    public function main()
    {
    }
}
