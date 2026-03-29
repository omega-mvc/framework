<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Application\Application;
use Omega\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'config:clear',
    description: 'Remove the configuration cache file'
)]
final class ConfigClearCommand extends AbstractCommand
{
    protected function handle(): int
    {
        $cachePath = Application::getInstance()->getApplicationCachePath() . 'config.php';

        if (!file_exists($cachePath)) {
            $this->warn('No configuration cache file found.');
            return self::SUCCESS;
        }

        if (@unlink($cachePath)) {
            $this->success('Configuration cache cleared successfully.');
            return self::SUCCESS;
        }

        $this->error('Could not remove the configuration cache file.');
        return self::FAILURE;
    }
}
