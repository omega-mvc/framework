<?php

/**
 * Part of Omega - View Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\View;

use function array_key_exists;
use function file_get_contents;

/**
 * Provides basic file content caching for templators.
 *
 * This trait allows templator classes to cache the contents of template files
 * in memory during a parsing cycle, reducing repeated filesystem access.
 *
 * The cache is expected to be defined as a static property (`self::$cache`)
 * in the consuming class.
 *
 * @category  Omega
 * @package   View
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
trait InteractWithCacheTrait
{
    /**
     * Retrieves the contents of a file, using an in-memory cache when available.
     *
     * If the file contents have already been loaded during the current parsing
     * process, the cached version is returned instead of reading the file again.
     *
     * @param string $fileName Absolute path to the template file.
     * @return string The contents of the file.
     */
    private function getContents(string $fileName): string
    {
        if (false === array_key_exists($fileName, self::$cache)) {
            self::$cache[$fileName] = file_get_contents($fileName);
        }

        return self::$cache[$fileName];
    }
}
