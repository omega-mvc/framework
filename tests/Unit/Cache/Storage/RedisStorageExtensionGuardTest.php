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

use function array_filter;
use function array_pop;
use function array_values;
use function in_array;
use function is_array;
use function is_string;

/**
 * Read the extensions currently toggled off through the guard global.
 *
 * Shielding the {@see $GLOBALS} toggle behind this typed accessor keeps the
 * test shadow of {@see extension_loaded} and the guard test free of raw
 * (mixed) global access.
 *
 * @return list<string> The extensions disabled for the current test run.
 */
function omegaDisabledExtensions(): array
{
    $disabled = $GLOBALS['omega_test_disabled_extensions'] ?? [];

    if (!is_array($disabled)) {
        return [];
    }

    return array_values(array_filter($disabled, 'is_string'));
}

/**
 * Toggle an extension off for the current test run.
 */
function omegaDisableExtension(string $extension): void
{
    $disabled   = omegaDisabledExtensions();
    $disabled[] = $extension;

    $GLOBALS['omega_test_disabled_extensions'] = $disabled;
}

/**
 * Restore the most recently toggled-off extension.
 */
function omegaRestoreDisabledExtension(): void
{
    $disabled = omegaDisabledExtensions();

    array_pop($disabled);

    $GLOBALS['omega_test_disabled_extensions'] = $disabled;
}

/**
 * Shadows {@see extension_loaded} within the Omega\Cache\Storage namespace so
 * the extension guard branch of {@see RedisStorage::isSupported()} can be
 * exercised in-process.
 *
 * It delegates to the real function unless the given extension is present in
 * the toggle list under the 'omega_test_disabled_extensions' key, which the
 * guard test populates around a try/finally block.
 *
 * @param string $extension The extension name to check.
 * @return bool True when the extension is reported as loaded.
 */
function extension_loaded(string $extension): bool
{
    if (in_array($extension, omegaDisabledExtensions(), true)) {
        return false;
    }

    return \extension_loaded($extension);
}

$GLOBALS['omega_test_disabled_extensions'] = omegaDisabledExtensions();

covers(RedisStorage::class);

it('reports unsupported when the redis extension is not loaded', function (): void {
    omegaDisableExtension('redis');

    try {
        expect(RedisStorage::isSupported())->toBeFalse();
    } finally {
        omegaRestoreDisabledExtension();
    }
});

it('reports supported by delegating to the real extension state', function (): void {
    if (\extension_loaded('redis') && false === RedisStorage::isSupported()) {
        $this->markTestSkipped('Could not connect to Redis server.');
    }

    expect(RedisStorage::isSupported())->toBe(\extension_loaded('redis'));
});