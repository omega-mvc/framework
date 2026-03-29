<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;
use Omega\Cache\CacheManager;

#[AsCommand(
    name: 'cache:clear',
    description: 'Clear the application cache (default or specific drivers)'
)]
final class CacheClearCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Clear all registered cache drivers')
            ->addOption('drivers', 'd', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Clear specific driver name(s)');
    }

    /**
     * @return int Exit code
     */
    protected function handle(): int
    {
        // 1. Verifica se il servizio cache è presente nel container
        if (!$this->app->has('cache')) {
            $this->error('Cache is not set yet.');
            return self::FAILURE;
        }

        /** @var CacheManager $cache */
        $cache = $this->app['cache'];
        $driversToClear = [];

        // 2. Recupero le opzioni
        $clearAll = $this->option('all');
        $specificDrivers = $this->option('drivers');

        // 3. Logica di selezione dei driver
        if ($clearAll) {
            // Accediamo alla proprietà 'driver' del CacheManager tramite closure scope (come nel tuo originale)
            $driversToClear = array_keys(
                (fn (): array => $this->{'driver'})->call($cache)
            );
        } elseif (!empty($specificDrivers)) {
            $driversToClear = $specificDrivers;
        }

        // 4. Esecuzione del clearing
        if (empty($driversToClear)) {
            // Caso default: solo il driver predefinito
            $cache->getDriver()->clear();
            $this->success('Done! Default cache driver has been cleared.');
            return self::SUCCESS;
        }

        foreach ($driversToClear as $driverName) {
            try {
                $driver = $cache->getDriver($driverName);

                // Verifica se il driver è supportato prima di procedere
                if (method_exists($driver, 'isSupported') && !$driver->isSupported()) {
                    $this->warn("Skipping '{$driverName}' driver: not supported.");
                    continue;
                }

                $driver->clear();
                $this->info("Cleared '{$driverName}' driver.");
            } catch (\Exception $e) {
                $this->error("Failed to clear '{$driverName}': " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
