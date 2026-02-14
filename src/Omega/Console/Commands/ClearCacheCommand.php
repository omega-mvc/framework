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

namespace Omega\Console\Commands;

use Omega\Application\Application;
use Omega\Cache\CacheManager;
use Omega\Cache\Exceptions\UnknownStorageException;
use Omega\Console\AbstractCommand;
use Omega\Console\Traits\CommandTrait;
use Omega\Container\Exceptions\CircularAliasException;

use function array_keys;
use function is_array;
use function Omega\Console\error;
use function Omega\Console\info;
use function Omega\Console\success;

/**
 * Class ClearCacheCommand
 *
 * Command to clear cache for the application. Supports clearing the default
 * cache driver, all registered drivers, or specific drivers specified by the user.
 *
 * This command integrates with the Application's cache system and provides
 * options for selective cache clearing.
 *
 * @category   Omega
 * @package    Console
 * @subpackage Commands
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 *
 * @property bool $update
 * @property bool $force
 */
class ClearCacheCommand extends AbstractCommand
{
    use CommandTrait;

    /**
     * Command registration configuration.
     *
     * Defines the pattern used to invoke the command and the method to execute.
     *
     * @var array<int, array<string, mixed>>
     */
    public static array $command = [
        [
            'pattern' => 'cache:clear',
            'fn'      => [ClearCacheCommand::class, 'clear'],
        ],
    ];

    /**
     * Returns a description of the command, its options, and their relations.
     *
     * This is used to generate help output for users.
     *
     * @return array<string, array<string, string|string[]>>
     */
    public function printHelp(): array
    {
        return [
            'commands'  => [
                'cache:clear' => 'Clear cache (default drive)',
            ],
            'options'   => [
                '--all'     => 'Clear all registered cache drivers.',
                '--drivers' => 'Clear specific driver name(s).',
            ],
            'relation'  => [
                'cache:clear' => ['--all', '--drivers'],
            ],
        ];
    }

    /**
     * Executes the cache clearing operation.
     *
     * Clears the default cache driver if no options are provided. If the
     * `--all` option is used, all registered drivers will be cleared. If
     * the `--drivers` option is provided, only the specified drivers will
     * be cleared.
     *
     * @param Application $app The application instance containing cache services.
     * @return int Exit code: 0 on success, 1 if cache is not configured.
     * @throws CircularAliasException Thrown when cache alias resolution loops recursively.
     * @throws UnknownStorageException If a requested cache storage driver is unknown, unregistered, or unsupported.
     */
    public function clear(Application $app): int
    {
        if (false === $app->has('cache')) {
            error('Cache is not set yet.')->out();

            return 1;
        }

        /** @var CacheManager|null $cache */
        $cache = $app['cache'];

        /** @var string[]|null $drivers */
        $drivers = null;

        /** @var string[]|string|bool $userDrivers */
        $userDrivers = $this->option('drivers', false);

        if ($this->option('all', false) && false === $userDrivers) {
            $drivers = array_keys(
                (fn (): array => $this->{'driver'})->call($cache)
            );
        }

        if ($userDrivers) {
            $drivers = is_array($userDrivers) ? $userDrivers : [$userDrivers];
        }

        if (null === $drivers) {
            $cache->getDriver()->clear();
            success('Done default cache driver has been clear.')->out(false);

            return 0;
        }

        foreach ($drivers as $driverName) {
            $driver = $cache->getDriver($driverName);

            if (method_exists($driver, 'isSupported') && !$driver->isSupported()) {
                info("Skipping '$driverName' driver: not supported.")->out(false);
                continue;
            }

            $driver->clear();
            info("Cleared '$driverName' driver.")->out(false);
        }

        return 0;
    }
}
