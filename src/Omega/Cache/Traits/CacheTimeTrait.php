<?php

/**
 * Part of Omega - Cache Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Cache\Traits;

use function microtime;
use function round;
use function time;


/**
 * Trait CacheTimeTrait
 *
 * Provides utilities to generate precise timestamps for cache items.
 *
 * This trait is intended for use by cache storage classes to calculate
 * creation or modification times (`mtime`) with sub-second precision.
 * It ensures that cache expiration, invalidation, and profiling operations
 * can rely on accurate timing, even when millisecond-level accuracy is needed.
 *
 * @category   Omega
 * @package    Cache
 * @subpackage Traits
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
trait CacheTimeTrait
{
    /**
     * Calculates a precise timestamp including microseconds.
     *
     * The returned value represents the current time with millisecond precision,
     * combining the integer seconds from `time()` with the fractional seconds from
     * `microtime(true)`. Useful for tracking creation or modification times
     * of cache items with high accuracy.
     *
     * @return float Timestamp rounded to milliseconds.
     */
    public function createMtime(): float
    {
        $currentTime = time();
        $microtime   = microtime(true);

        $fractionalPart = $microtime - $currentTime;

        if ($fractionalPart >= 1) {
            $currentTime += (int) $fractionalPart;
            $fractionalPart -= (int) $fractionalPart;
        }

        $mtime = $currentTime + $fractionalPart;

        return round($mtime, 3);
    }
}
