<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Exception;
use Omega\Cache\CacheManager;
use Omega\Cache\Exceptions\UnknownStorageException;
use Omega\Console\AbstractCommand;
use Omega\Console\Attribute\AsCommand;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use Symfony\Component\Console\Input\InputOption;

use function array_filter;
use function array_values;
use function is_array;
use function is_string;
use function method_exists;

#[AsCommand(
    name: 'cache:clear',
    description: 'Clear the application cache. Without options, clears the default cache driver',
    options: [
        'all'     => ['a', InputOption::VALUE_NONE, 'Clear all registered cache drivers'],
        'drivers' => ['d', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Clear specific driver name(s)']
    ]
)]
final class CacheClearCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     *
     * @throws BindingResolutionException
     * @throws CircularAliasException
     * @throws ContainerExceptionInterface
     * @throws EntryNotFoundException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws UnknownStorageException
     */
    public function __invoke(): int
    {
        if (!$this->app->has('cache')) {
            $this->io->error('Cache is not set yet.');
            return self::FAILURE;
        }

        /** @var CacheManager $cache */
        $cache = $this->app->get('cache');
        $clearAll = (bool) $this->getOption('all');
        $specificDrivers = $this->getOption('drivers');

        if ($clearAll && !empty($specificDrivers)) {
            $this->io->warning("'--all' overrides '--drivers': clearing all registered drivers.");
        }

        $driversToClear = [];

        if ($clearAll) {
            $driversToClear = $cache->getDriverNames();
        } elseif (is_array($specificDrivers)) {
            $driversToClear = array_values(array_filter(
                $specificDrivers,
                static fn (mixed $driver): bool => is_string($driver) && '' !== $driver
            ));
        }

        if ([] === $driversToClear) {
            return $this->clearDefaultDriver($cache);
        }

        return $this->clearDrivers($cache, $driversToClear);
    }

    /**
     * Clears the default cache driver and reports which cache was emptied.
     *
     * @param CacheManager $cache The cache manager instance.
     * @return int The command exit code.
     */
    private function clearDefaultDriver(CacheManager $cache): int
    {
        $driverName = $cache->getDefaultDriverName();

        try {
            $cleared = $cache->getDriver()->clear();
        } catch (Exception $e) {
            $this->io->error("Failed to clear '{$driverName}' driver: " . $e->getMessage());

            return self::FAILURE;
        }

        if (false === $cleared) {
            $this->io->error("Failed to clear '{$driverName}' driver.");

            return self::FAILURE;
        }

        $this->io->info("Cleared '{$driverName}' driver.");

        return self::SUCCESS;
    }

    /**
     * Clears the given cache drivers, reporting per-driver results.
     *
     * @param CacheManager $cache   The cache manager instance.
     * @param list<string> $drivers The driver names to clear.
     * @return int The command exit code.
     */
    private function clearDrivers(CacheManager $cache, array $drivers): int
    {
        $failed = false;

        foreach ($drivers as $driverName) {
            try {
                $driver = $cache->getDriver($driverName);

                if (method_exists($driver, 'isSupported') && !$driver->isSupported()) {
                    $this->io->warning("Skipping '{$driverName}' driver: not supported.");
                    continue;
                }

                $cleared = $driver->clear();

                if (false === $cleared) {
                    $this->io->error("Failed to clear '{$driverName}' driver.");
                    $failed = true;
                    continue;
                }

                $this->io->info("Cleared '{$driverName}' driver.");
            } catch (Exception $e) {
                $this->io->error("Failed to clear '{$driverName}': " . $e->getMessage());
                $failed = true;
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
