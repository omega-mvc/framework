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

namespace Omega\Console\Style;

use Omega\Console\Traits\CommandTrait;

use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function call_user_func;
use function ceil;
use function str_pad;
use function str_repeat;
use function str_replace;

use const PHP_EOL;

/**
 * Class ProgressBar
 *
 * A simple terminal progress bar implementation with customizable templates and bindings.
 * It supports automatic updates in the console and allows for custom callbacks when tasks complete.
 *
 * Example usage:
 * ```php
 * $bar = new ProgressBar();
 * $bar->mask = 100;
 * for ($i = 0; $i < 100; $i++) {
 *     $bar->current = $i + 1;
 *     $bar->tick();
 * }
 * ```
 *
 * @category   Omega
 * @package    Console
 * @subpackage Style
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class ProgressBar
{
    use CommandTrait;

    /** @var string Template for rendering the progress bar (e.g. ':progress :percent'). */
    private string $template;

    /** @var int Current progress value. */
    public int $current = 0;

    /** @var int Maximum value of progress. */
    public int $mask = 1;

    /** @var string Current rendered progress string. */
    private string $progress;

    /** @var callable(): string Callback executed when the task completes. */
    public $complete;

    /** @var array<callable(int, int): string> Bindings for template placeholders. */
    private array $binds;

    /** @var array<callable(int, int): string> Global custom bindings available for all instances. */
    public static array $customBinds = [];

    /**
     * ProgressBar constructor.
     *
     * @param string $template Template string with placeholders (default ':progress :percent')
     * @param array<callable(int, int): string> $binds Optional custom bindings for template placeholders
     */
    public function __construct(string $template = ':progress :percent', array $binds = [])
    {
        $this->progress = '';
        $this->template = $template;
        $this->complete = fn (): string => $this->complete();

        $this->binding($binds);
    }

    /**
     * Convert progress bar object to string representation.
     *
     * @return string Rendered progress bar with applied bindings
     */
    public function __toString(): string
    {
        $bindsValues = array_map(
            fn($bind) => $bind($this->current, $this->mask),
            $this->binds
        );

        return str_replace(array_keys($this->binds), $bindsValues, $this->template);
    }

    /**
     * Increment the progress bar by one tick and update console output.
     *
     * @return void
     */
    public function tick(): void
    {
        $this->progress = (string) $this;
        new Style()->replace($this->progress);

        if ($this->current + 1 > $this->mask) {
            $complete = (string) call_user_func($this->complete);
            new Style()->clear();
            new Style()->replace($complete . PHP_EOL);
        }
    }

    /**
     * Update the progress bar with a custom template and bindings.
     *
     * @param string $template Template string for rendering progress bar
     * @param array<callable(int, int): string> $binds Custom bindings for placeholders
     * @return void
     */
    public function tickWith(string $template = ':progress :percent', array $binds = []): void
    {
        $this->template = $template;
        $this->binding($binds);
        $this->progress = (string) $this;
        new Style()->replace($this->progress);

        if ($this->current + 1 > $this->mask) {
            $complete = (string) call_user_func($this->complete);
            new Style()->clear();
            new Style()->replace($complete . PHP_EOL);
        }
    }

    /**
     * Generate a textual representation of the progress bar.
     *
     * @param int $current Current progress
     * @param int $maks Maximum progress
     * @return string Textual progress bar (e.g. '[=====----]')
     */
    private function progress(int $current, int $maks): string
    {
        $length = 20;
        $tick   = (int) ceil($current * ($length / $maks)) - 1;
        $head   = $current === $maks ? '=' : '>';
        $bar    = str_repeat('=', $tick) . $head;
        $left   = '-';

        return '[' . str_pad($bar, $length, $left) . ']';
    }

    /**
     * Bind template placeholders to their respective callbacks.
     *
     * @param array<callable(int, int): string> $binds Custom bindings to merge with default ones
     * @return void
     */
    public function binding(array $binds): void
    {
        $binds = array_merge($binds, self::$customBinds);
        if (false === array_key_exists(':progress', $binds)) {
            $binds[':progress'] =  fn ($current, $maks): string => $this->progress($current, $maks);
        }

        if (false === array_key_exists(':percent', $binds)) {
            $binds[':percent'] =  fn ($current, $maks): string => ceil(($current / $maks) * 100) . '%';
        }
        $this->binds = $binds;
    }

    /**
     * Return the final completed progress string.
     *
     * @return string Rendered progress bar at completion
     */
    private function complete(): string
    {
        return $this->progress;
    }
}
