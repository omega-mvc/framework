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

namespace Omega\Redis;

use function array_pop;
use function in_array;

/**
 * Shadows {@see extension_loaded} within the Omega\Redis namespace so the
 * extension guard branches of {@see Redis}, {@see RedisConnector} and
 * {@see RedisManager} can be exercised in-process.
 *
 * It delegates to the real function unless the given extension is present in
 * the {@see $GLOBALS} toggle list under the 'omega_test_disabled_extensions'
 * key, which the guard tests populate around a try/finally block.
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

covers(Redis::class);
covers(RedisConnector::class);
covers(RedisManager::class);

it('throws when the redis extension is not loaded', function (): void {
    $GLOBALS['omega_test_disabled_extensions'][] = 'redis';

    try {
        expect(fn (): Redis => new Redis([]))
            ->toThrow(\RuntimeException::class, 'The Redis extension is not loaded.');

        expect(fn (): object => (new RedisConnector())->connect([]))
            ->toThrow(\RuntimeException::class, 'The Redis extension is not loaded.');

        expect(RedisManager::isSupported())->toBeFalse();
    } finally {
        array_pop($GLOBALS['omega_test_disabled_extensions']);
    }
});

it('delegates to the real extension state outside the guard toggle', function (): void {
    expect(RedisManager::isSupported())->toBe(\extension_loaded('redis'));
});