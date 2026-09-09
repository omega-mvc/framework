<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Omega\Console\Attribute\AsCommand;

use function file_exists;
use function unlink;

#[AsCommand(
    name: 'command:clear',
    description: 'Remove the cached console commands map',
)]
final class CommandClearCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(): int
    {
        $cachePath = $this->app->getApplicationCachePath() . 'commands.php';

        if (!file_exists($cachePath)) {
            $this->io->warning('No console cache file found. Nothing to clear.');
            return self::SUCCESS;
        }

        if (unlink($cachePath)) {
            $this->io->info("Console command cache cleared successfully.");
            $this->io->comment("The application will now use dynamic discovery.");

            return self::SUCCESS;
        }

        $this->io->error("Failed to delete the cache file at: {$cachePath}");

        return self::FAILURE;
    }
}
