<?php

/**
 * Part of Omega - Macroable Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Macroable;

use Closure;
use Omega\Macroable\Exceptions\MacroNotFoundException;

use function array_key_exists;

/**
 * Provides the ability to dynamically register methods ("macros") at runtime.
 *
 * Classes using this trait can define new callable behaviors without modifying
 *
 * @category  Omega
 * @package   Macroable
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
trait MacroableTrait
{
    /** @var array<string, callable> List of macros indexed by macro name. */
    protected static array $macros = [];

    /**
     * Register a new macro.
     *
     * @param string   $macroName The name of the macro (method name).
     * @param callable $callBack  The callable implementation of the macro.
     * @return void
     */
    public static function macro(string $macroName, callable $callBack): void
    {
        self::$macros[$macroName] = $callBack;
    }

    /**
     * Handle dynamic static method calls.
     *
     * @param string              $method     The macro name.
     * @param array<int, mixed>   $parameters Parameters passed to the macro.
     * @return mixed The result of the macro call.
     * @throws MacroNotFoundException If the macro is not registered.
     */
    public static function __callStatic(string $method, array $parameters)
    {
        if (!array_key_exists($method, self::$macros)) {
            throw new MacroNotFoundException($method);
        }

        $macro = static::$macros[$method];

        if ($macro instanceof Closure) {
            $macro = $macro->bindTo(null, static::class);
        }

        return $macro(...$parameters);
    }

    /**
     * Handle dynamic instance method calls.
     *
     * @param string              $method     The macro name.
     * @param array<int, mixed>   $parameters Parameters passed to the macro.
     * @return mixed The result of the macro call.
     * @throws MacroNotFoundException If the macro is not registered.
     */
    public function __call(string $method, array $parameters)
    {
        if (!array_key_exists($method, self::$macros)) {
            throw new MacroNotFoundException($method);
        }

        $macro = static::$macros[$method];

        if ($macro instanceof Closure) {
            $macro = $macro->bindTo($this, static::class);
        }

        return $macro(...$parameters);
    }

    /**
     * Determine if a macro is registered.
     *
     * @param string $macroName The macro name to check.
     * @return bool True if the macro is registered, false otherwise.
     */
    public static function hasMacro(string $macroName): bool
    {
        return array_key_exists($macroName, self::$macros);
    }

    /**
     * Clear all registered macros.
     *
     * @return void
     */
    public static function resetMacro(): void
    {
        self::$macros = [];
    }
}
