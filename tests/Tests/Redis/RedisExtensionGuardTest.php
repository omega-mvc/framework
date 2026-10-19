<?php

declare(strict_types=1);

namespace Omega\Redis;

use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

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
 * test shadow of {@see extension_loaded} and the guard tests free of raw
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
 * Shadows {@see extension_loaded} within the Omega\Redis namespace so the
 * extension guard branches of {@see Redis}, {@see RedisConnector} and
 * {@see RedisManager} can be exercised in-process.
 *
 * It delegates to the real function unless the given extension is present in
 * the toggle list under the 'omega_test_disabled_extensions' key, which the
 * guard tests populate around a try/finally block.
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

#[CoversClass(Redis::class)]
#[CoversClass(RedisConnector::class)]
#[CoversClass(RedisManager::class)]
final class RedisExtensionGuardTest extends TestCase
{
    public function testThrowsWhenTheRedisExtensionIsNotLoaded(): void
    {
        omegaDisableExtension('redis');

        try {
            self::assertRedisGuardThrows(fn (): Redis => new Redis([]));
            self::assertRedisGuardThrows(fn (): object => (new RedisConnector())->connect([]));
            $this->assertFalse(RedisManager::isSupported());
        } finally {
            omegaRestoreDisabledExtension();
        }
    }

    public function testDelegatesToTheRealExtensionStateOutsideTheGuardToggle(): void
    {
        $this->assertSame(\extension_loaded('redis'), RedisManager::isSupported());
    }

    /**
     * Assert the given callback fails with the Redis guard RuntimeException.
     *
     * @param callable $callback The callback expected to throw.
     * @return void
     */
    private static function assertRedisGuardThrows(callable $callback): void
    {
        try {
            $callback();
        } catch (\RuntimeException $e) {
            self::assertSame('The Redis extension is not loaded.', $e->getMessage());

            return;
        }

        self::fail('Expected a RuntimeException stating the Redis extension is not loaded.');
    }
}
