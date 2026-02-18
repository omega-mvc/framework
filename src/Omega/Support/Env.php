<?php

/**
 * Part of Omega - Facades Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Support;

use Dotenv\Dotenv;

use function is_numeric;
use function strtolower;

/**
 * Env class for loading and accessing environment variables.
 *
 * This class allows loading environment variables from a file and provides
 * a convenient way to retrieve them with automatic type casting for common values.
 *
 * @category  Omega
 * @package   Support
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class Env
{
    /**
     * @var array<string, mixed> Stores the loaded environment variables.
     */
    protected static array $values = [];

    /**
     * Load environment variables from a given path and file.
     *
     * @param string $path The directory path containing the environment file.
     * @param string $file The environment filename, defaults to '.env'.
     * @return void
     */
    public static function load(string $path, string $file = '.env'): void
    {
        $dotenv = Dotenv::createImmutable($path, $file);
        self::$values = $dotenv->load();
    }

    /**
     * Retrieve an environment variable by key with optional default value.
     *
     * Automatically converts string values to proper types:
     * - "true" or "(true)" => true
     * - "false" or "(false)" => false
     * - "null" or "(null)" => null
     * - "empty" or "(empty)" => empty string
     * - numeric strings => integers or floats
     *
     * @param string $key The environment variable key to retrieve.
     * @param mixed $default The default value to return if the key is not found.
     * @return mixed The value of the environment variable, cast if applicable, or $default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, self::$values)) {
            $value = self::$values[$key];
        } else {
            $envValue = getenv($key);
            $value = ($envValue !== false) ? $envValue : $default;
        }

        if (is_string($value)) {
            $lower = strtolower($value);
            return match ($lower) {
                'true', '(true)'   => true,
                'false', '(false)' => false,
                'null', '(null)'   => null,
                'empty', '(empty)' => '',
                default            => is_numeric($value) ? $value + 0 : $value,
            };
        }

        return $value;
    }
}
