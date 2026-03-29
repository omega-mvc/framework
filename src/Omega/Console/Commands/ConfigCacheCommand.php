<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Application\Application;
use Omega\Config\ConfigRepository;
use Omega\Console\AbstractCommand;
use Omega\Support\Bootstrap\ConfigProviders;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

#[AsCommand(
    name: 'config:cache',
    description: 'Create a cache file for faster configuration loading'
)]
final class ConfigCacheCommand extends AbstractCommand
{
    protected function handle(): int
    {
        $this->info('Caching the framework configuration...');

        try {
            $app = Application::getInstance();

            // Re-bootstrappiamo i provider per essere sicuri di avere i valori freschi
            (new ConfigProviders())->bootstrap($app);

            // Puliamo la cache esistente prima di scriverne una nuova
            $cachePath = $app->getApplicationCachePath() . 'config.php';
            if (file_exists($cachePath)) {
                @unlink($cachePath);
            }

            $config = $app->get(ConfigRepository::class)->getAll();
            $exported = '<?php return ' . var_export($config, true) . ';' . PHP_EOL;

            if (file_put_contents($cachePath, $exported) === false) {
                $this->error('Failed to write the configuration cache file.');
                return self::FAILURE;
            }

            $this->success('Configuration cached successfully!');
            return self::SUCCESS;

        } catch (Throwable $e) {
            $this->error('An error occurred while caching configuration: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
