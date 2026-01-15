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
use Omega\Console\Exceptions\ImmutableCommandMapException;
use Omega\Text\Str;
use ReturnTypeWillChange;

use function array_key_exists;
use function is_array;

/**
 * Class CommandMap
 *
 * Represents a single console command definition, including its name(s),
 * matching patterns, execution target (class and method), and default options.
 *
 * The CommandMap acts as a wrapper around an associative array describing
 * how a console command should be registered, matched, and executed.
 *
 * Example structure of a command definition:
 * ```php
 * [
 *   'cmd'      => ['make:model', 'make:migration'],   // Command names
 *   'pattern'  => ['make', 'create'],                 // Optional pattern(s)
 *   'class'    => App\Console\MakeCommand::class,     // Target class
 *   'fn'       => 'handle',                           // Method or [class, method]
 *   'mode'     => 'full',                             // Match mode: "full" or prefix
 *   'default'  => ['force' => false, 'name' => null], // Default options
 *   'match'    => fn(string $given): bool => ...      // Custom matcher (optional)
 * ]
 * ```
 *
 * @category  Omega
 * @package   Console
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 *
 * @implements ArrayAccess<string, string|string[]|(array<string, string|bool|int|null>)|(callable(string): bool)>
 */
readonly class CommandMap implements ArrayAccess
{
    /**
     * Create a new CommandMap instance.
     *
     * @param array<string, string|string[]|array<string, string|bool|int|null>|callable(string):bool> $command
     */
    public function __construct(
        private array $command
    ) {
    }

    /**
     * Get the command name(s).
     *
     * Returns the "cmd" key as an array, even if a single command string
     * was defined.
     *
     * @return string[]
     */
    public function cmd(): array
    {
        if (false === array_key_exists('cmd', $this->command)) {
            return [];
        }
        $cmd = $this->command['cmd'];

        return is_array($cmd) ? $cmd : [$cmd];
    }

    /**
     * Get the matching mode for this command.
     *
     * The mode determines how the input is compared to command names.
     * Supported modes:
     *  - "full": requires an exact match
     *  - "prefix": allows partial matches by prefix
     *
     * Defaults to "full".
     *
     * @return string
     */
    public function mode(): string
    {
        return $this->command['mode'] ?? 'full';
    }

    /**
     * Get the pattern(s) defined for this command.
     *
     * Patterns are alternative identifiers for the command.
     * Always returned as an array.
     *
     * @return string[]
     */
    public function patterns(): array
    {
        if (false === array_key_exists('pattern', $this->command)) {
            return [];
        }

        $pattern = $this->command['pattern'];

        return is_array($pattern) ? $pattern : [$pattern];
    }

    /**
     * Resolve the target class for the command execution.
     *
     * Uses either:
     *  - The first element of the "fn" array (if fn is [class, method]), or
     *  - The "class" key in the definition.
     *
     * @return string
     * @throws InvalidArgumentException If no class can be resolved.
     */
    public function class(): string
    {
        if (is_array($this->fn()) && array_key_exists(0, $this->fn())) {
            return $this->fn()[0];
        }

        if (array_key_exists('class', $this->command)) {
            return $this->command['class'];
        }

        throw new InvalidArgumentException('Command map require class in (class or fn).');
    }

    /**
     * Get the execution target ("fn") for this command.
     *
     * Can be either:
     *  - A string method name, or
     *  - An array [class, method].
     *
     * Defaults to "main".
     *
     * @return string|string[]
     */
    public function fn(): string|array
    {
        return $this->command['fn'] ?? 'main';
    }

    /**
     * Get the target method name to invoke.
     *
     * If "fn" is an array, returns its second element.
     * If "fn" is a string, returns that string.
     *
     * @return string
     */
    public function method(): string
    {
        return is_array($this->fn()) ? $this->fn()[1] : $this->fn();
    }
    /**
     * Get the default options for this command.
     *
     * Options are defined under the "default" key in the definition.
     *
     * @return array<string, string|bool|int|null>
     */
    public function defaultOption(): array
    {
        return $this->command['default'] ?? [];
    }

    /**
     * Build and return a callable matcher for this command.
     *
     * The matcher is responsible for determining whether a given
     * command string matches this definition.
     *
     * Priority:
     *  1. Use "pattern" key if defined
     *  2. Use "match" callback if defined
     *  3. Fallback to comparing against "cmd" values
     *
     * @return callable(string): bool
     */
    public function match(): callable
    {
        if (array_key_exists('pattern', $this->command)) {
            $pattern  = $this->command['pattern'];
            $patterns = is_array($pattern) ? $pattern : [$pattern];

            return function ($given) use ($patterns): bool {
                foreach ($patterns as $cmd) {
                    if ($given == $cmd) {
                        return true;
                    }
                }

                return false;
            };
        }

        if (array_key_exists('match', $this->command)) {
            return $this->command['match'];
        }

        if (array_key_exists('cmd', $this->command)) {
            return function ($given): bool {
                foreach ($this->cmd() as $cmd) {
                    if ('full' === $this->mode()) {
                        if ($given == $cmd) {
                            return true;
                        }
                    }

                    if (Str::startsWith($given, $cmd)) {
                        return true;
                    }
                }

                return false;
            };
        }

        return fn ($given) => false;
    }

    /**
     * Test whether a given input matches this command.
     *
     * Internally executes the matcher returned by {@see match()}.
     *
     * @param string $given The user-provided command string.
     * @return bool True if the input matches this command.
     */
    public function isMatch(string $given): bool
    {
        return ($this->match())($given);
    }

    /**
     * Get the callable array for execution.
     *
     * Returns a [class, method] pair suitable for use with call_user_func().
     *
     * @return string[]
     */
    public function call(): array
    {
        return is_array($this->fn())
            ? $this->fn()
            : [$this->class(), $this->fn()];
    }

    /**
     * Get the callable array for execution.
     *
     * Returns a [class, method] pair suitable for use with call_user_func().
     *
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->command);
    }

    /**
     * Retrieve a value from the command definition by key.
     *
     * @param mixed $offset
     * @return string|string[]|array<string, string|bool|int|null>|callable(string):bool
     */
    #[ReturnTypeWillChange]
    public function offsetGet(mixed $offset): mixed
    {
        return $this->command[$offset];
    }

    /**
     * Prevent reassignment of definition keys.
     *
     * @param mixed $offset
     * @param mixed $value
     * @throws ImmutableCommandMapException Always, as reassignment is not allowed.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new ImmutableCommandMapException('CommandMap cant be reassignment');
    }

    /**
     * Prevent unsetting of definition keys.
     *
     * @param mixed $offset
     * @throws ImmutableCommandMapException Always, as unsetting is not allowed.
     */
    public function offsetUnset(mixed $offset): void
    {
        throw new ImmutableCommandMapException('CommandMap cant be reassignment');
    }
}
