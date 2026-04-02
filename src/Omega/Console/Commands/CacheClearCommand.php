<?php

/** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */

declare(strict_types=1);

namespace Omega\Console\Commands;

use Exception;
use Omega\Cache\CacheManager;
use Omega\Cache\Exceptions\UnknownStorageException;
use Omega\Console\AbstractCommand;
use Omega\Console\Attribute\AsCommand;
use Omega\Container\Exceptions\CircularAliasException;
use Symfony\Component\Console\Input\InputOption;

use function array_keys;
use function method_exists;

#[AsCommand(
    name: 'cache:clear',
    description: 'Clear the application cache (default or specific drivers)',
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
     * @throws CircularAliasException
     * @throws UnknownStorageException
     */
    public function __invoke(): int
    {
        if (!$this->app->has('cache')) {
            $this->io->error('Cache is not set yet.');
            return self::FAILURE;
        }

        /** @var CacheManager $cache */
        $cache = $this->app['cache'];
        $driversToClear = [];

        $clearAll = $this->getOption('all');
        $specificDrivers = $this->getOption('drivers');

        if ($clearAll) {
            $driversToClear = array_keys(
                (fn (): array => $this->{'driver'})->call($cache)
            );
        } elseif (!empty($specificDrivers)) {
            $driversToClear = $specificDrivers;
        }

        if (empty($driversToClear)) {
            $cache->getDriver()->clear();
            $this->io->success('Done! Default cache driver has been cleared.');
            return self::SUCCESS;
        }

        foreach ($driversToClear as $driverName) {
            try {
                $driver = $cache->getDriver($driverName);

                if (method_exists($driver, 'isSupported') && !$driver->isSupported()) {
                    $this->io->warning("Skipping '{$driverName}' driver: not supported.");
                    continue;
                }

                $driver->clear();
                $this->io->info("Cleared '{$driverName}' driver.");
            } catch (Exception $e) {
                $this->io->error("Failed to clear '{$driverName}': " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
