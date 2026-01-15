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

use Exception;
use Omega\Console\Style\Alert;
use Omega\Console\Style\Style;
use Omega\Console\Traits\TerminalTrait;

use function constant;
use function defined;
use function function_exists;
use function pcntl_signal;
use function posix_getgid;
use function posix_kill;
use function sapi_windows_set_ctrl_handler;

use const PHP_SAPI;
use const PHP_WINDOWS_EVENT_CTRL_C;

/**
 * Console Helper Functions
 *
 * This file provides a collection of global helper functions to simplify
 * interaction with the Omega console environment. They cover tasks such as:
 *
 * - Rendering styled output in the terminal (info, warn, error, success, style).
 * - Prompting the user for input (option, select, text, password, any_key).
 * - Managing terminal width detection (width).
 * - Handling exit signals such as Ctrl+C (exit_prompt, remove_exit_prompt).
 *
 * These helpers are designed to improve developer experience by providing
 * expressive shortcuts to common console operations, making command-line
 * interactions more fluent and user-friendly.
 * ConsoleError integrates Whoops error handling into the console application.
 *
 * @category  Omega
 * @package   Console
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
if (!function_exists('style')) {
    /**
     * Create a styled text instance for terminal output.
     *
     * @param string $text The text to be styled.
     * @return Style A Style instance for chaining terminal styles.
     */
    function style(string $text): Style
    {
        return new Style($text);
    }
}

if (!function_exists('info')) {
    /**
     * Render an "info" alert in the terminal.
     *
     * @param string $text The message text.
     * @return Style The styled alert output.
     */
    function info(string $text): Style
    {
        return Alert::render()->info($text);
    }
}

if (!function_exists('warn')) {
    /**
     * Render a "warning" alert in the terminal.
     *
     * @param string $text The message text.
     * @return Style The styled alert output.
     */
    function warn(string $text): Style
    {
        return Alert::render()->warn($text);
    }
}

if (!function_exists('error')) {
    /**
     * Render a "failure" alert in the terminal.
     *
     * @param string $text The message text.
     * @return Style The styled alert output.
     */
    function error(string $text): Style
    {
        return Alert::render()->error($text);
    }
}

if (!function_exists('success')) {
    /**
     * Render a "success" alert in the terminal.
     *
     * @param string $text The message text.
     * @return Style The styled alert output.
     */
    function success(string $text): Style
    {
        return Alert::render()->success($text);
    }
}

if (!function_exists('option')) {
    /**
     * Display a command prompt with selectable options.
     *
     * @param string|Style $title The prompt message.
     * @param array<string, callable> $options The available options mapped to callbacks.
     * @return mixed The result of the selected option callback.
     * @throws Exception If the prompt fails.
     */
    function option(string|Style $title, array $options): mixed
    {
        return new Prompt($title, $options)->option();
    }
}

if (!function_exists('select')) {
    /**
     * Display a command prompt with a list selection.
     *
     * @param string|Style $title The prompt message.
     * @param array<string, callable> $options The list of options mapped to callbacks.
     * @return mixed The result of the selected option callback.
     * @throws Exception If the prompt fails.
     */
    function select(string|Style $title, array $options): mixed
    {
        return new Prompt($title, $options)->select();
    }
}

if (!function_exists('text')) {
    /**
     * Prompt for text input from the user.
     *
     * @param string|Style $title The prompt message.
     * @param callable $callable A callback to handle the input.
     * @return mixed The callback result.
     * @throws Exception If the prompt fails.
     */
    function text(string|Style $title, callable $callable): mixed
    {
        return new Prompt($title)->text($callable);
    }
}

if (!function_exists('password')) {
    /**
     * Prompt for password input with optional masking.
     *
     * @param string|Style $title The prompt message.
     * @param callable $callable A callback to handle the input.
     * @param string $mask The character used to mask input (default: none).
     * @return mixed The callback result.
     */
    function password(string|Style $title, callable $callable, string $mask = ''): mixed
    {
        return new Prompt($title)->password($callable, $mask);
    }
}

if (!function_exists('any_key')) {
    /**
     * Prompt the user to press any key to continue.
     *
     * @param string|Style $title The prompt message.
     * @param callable $callable A callback to handle the keypress.
     * @return mixed The callback result.
     * @throws Exception If the prompt fails.
     */
    function any_key(string|Style $title, callable $callable): mixed
    {
        return new Prompt($title)->anyKey($callable);
    }
}

if (!function_exists('width')) {
    /**
     * Get the current terminal width within a specified range.
     *
     * @param int $min The minimum width.
     * @param int $max The maximum width.
     * @return int The calculated terminal width.
     */
    function width(int $min, int $max): int
    {
        $terminal = new class {
            use TerminalTrait;

            public function width(int $min, int $max): int
            {
                return $this->getWidth($min, $max);
            }
        };

        return $terminal->width($min, $max);
    }
}

if (!function_exists('exit_prompt')) {
    /**
     * Register a Ctrl+C event handler with confirmation prompt.
     *
     * @param string|Style $title The confirmation message.
     * @param array<string, callable>|null $options Custom options mapped to callbacks.
     * @return void
     * @throws Exception If registration fails.
     */
    function exit_prompt(string|Style $title, ?array $options = null): void
    {
        $signal = defined('SIGINT') ? constant('SIGINT') : 2;
        $options ??= [
            'yes' => static function () use ($signal) {
                if (function_exists('posix_kill') && function_exists('posix_getpid')) {
                    posix_kill(posix_getgid(), $signal);
                }

                exit(128 + $signal);
            },
            'no'  => fn () => null,
        ];

        if (function_exists('sapi_windows_set_ctrl_handler') && 'cli' === PHP_SAPI) {
            sapi_windows_set_ctrl_handler(static function (int $event) use ($title, $options) {
                if (PHP_WINDOWS_EVENT_CTRL_C === $event) {
                    new Style()->out();
                    new Prompt($title, $options, 'no')->option();
                }
            });
        }

        if (function_exists('pcntl_signal')) {
            pcntl_signal($signal, $options['yes']);
        }
    }
}

if (!function_exists('remove_exit_prompt')) {
    /**
     * Remove the registered Ctrl+C event handler, restoring default behavior.
     *
     * @return void
     */
    function remove_exit_prompt(): void
    {
        if (function_exists('sapi_windows_set_ctrl_handler') && 'cli' === PHP_SAPI) {
            sapi_windows_set_ctrl_handler(function (int $handler): void {
            }, false);
        }

        $signal  = defined('SIGINT') ? constant('SIGINT') : 2;
        $default = defined('SIG_DFL') ? constant('SIG_DFL') : 0;
        if (function_exists('pcntl_signal')) {
            pcntl_signal($signal, $default);
        }
    }
}
