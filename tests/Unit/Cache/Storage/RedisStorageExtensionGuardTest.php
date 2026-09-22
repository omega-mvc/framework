<?php

/**
 * Part of Omega - Tests Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Cache\Storage;

use function array_pop;
use function in_array;

/**
 * Shadows {@see extension_loaded} within the Omega\Cache\Storage namespace so
 * the extension guard branch of {@see RedisStorage::isSupported()} can be
 * exercised in-process.
 *
 * It delegates to the real function unless the given extension is present in
 * the {@see $GLOBALS} toggle list under the 'omega_test_disabled_extensions'
 * key, which the guard test populates around a try/finally block.
 *
 * @param string $extension The extension name to check.
 * @return bool True when the extension is reported as loaded.
 */
function extension_loaded(string $extension): bool
{
    if (in_array($extension, $GLOBALS['omega_test_disabled_extensions'] ?? [], true)) {
        return false;
    }

    return \extension_loaded($extension);
}

$GLOBALS['omega_test_disabled_extensions'] = $GLOBALS['omega_test_disabled_extensions'] ?? [];

covers(RedisStorage::class);

it('reports unsupported when the redis extension is not loaded', function (): void {
    $GLOBALS['omega_test_disabled_extensions'][] = 'redis';

    try {
        expect(RedisStorage::isSupported())->toBeFalse();
    } finally {
        array_pop($GLOBALS['omega_test_disabled_extensions']);
    }
});

it('reports supported by delegating to the real extension state', function (): void {
    if (\extension_loaded('redis') && false === RedisStorage::isSupported()) {
        $this->markTestSkipped('Could not connect to Redis server.');
    }

    expect(RedisStorage::isSupported())->toBe(\extension_loaded('redis'));
});