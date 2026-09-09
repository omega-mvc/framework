<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Omega\Console\Attribute\AsCommand;

use function file_exists;

#[AsCommand(
    name: 'config:clear',
    description: 'Remove the configuration cache file'
)]
final class ConfigClearCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(): int
    {
        $cachePath = $this->app->getApplicationCachePath() . 'config.php';

        if (!file_exists($cachePath)) {
            $this->io->warning('No configuration cache file found.');
            return self::SUCCESS;
        }

        if (@unlink($cachePath)) {
            $this->io->info('Configuration cache cleared successfully.');
            return self::SUCCESS;
        }

        $this->io->error('Could not remove the configuration cache file.');
        return self::FAILURE;
    }
}
