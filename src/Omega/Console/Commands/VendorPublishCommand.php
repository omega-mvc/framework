<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Helper\ProgressBar;
use Omega\Support\AbstractServiceProvider;

#[AsCommand(
    name: 'vendor:publish',
    description: 'Publish any publishable assets from vendor packages'
)]
final class VendorPublishCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->addOption('tag', 't', InputOption::VALUE_OPTIONAL, 'Specify the tag to run specific publishing', '*')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite existing files');
    }

    protected function handle(): int
    {
        $tag = $this->option('tag');
        $force = $this->option('force');

        $modules = AbstractServiceProvider::getModules();

        if (empty($modules)) {
            $this->warn('No publishable resources found.');
            return self::SUCCESS;
        }

        $this->publishItems($modules, $tag, $force);

        return self::SUCCESS;
    }

    /**
     * Handles the publication of modules filtered by tag.
     */
    private function publishItems(array $modules, string $targetTag, bool $force): void
    {
        $added = 0;

        // Filtering modules by tag
        $filtered = ($targetTag === '*')
            ? $modules
            : array_filter($modules, fn($tag) => $tag === $targetTag, ARRAY_FILTER_USE_KEY);

        if (empty($filtered)) {
            $this->error("No publishable resources found for tag: {$targetTag}");
            return;
        }

        $this->info("Publishing resources...");

        $progressBar = new ProgressBar($this->io, count($filtered));
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% -- %message%');
        $progressBar->start();

        foreach ($filtered as $tag => $module) {
            foreach ($module as $from => $to) {
                $progressBar->setMessage("Publishing resources for tag: <comment>{$tag}</comment>");

                $success = is_dir($from)
                    ? AbstractServiceProvider::importDir($from, $to, $force) // Qui il metodo del provider rimane import per ora
                    : AbstractServiceProvider::importFile($from, $to, $force);

                if ($success) {
                    $added++;
                }
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->io->newLine(2);

        $this->success("Done! <fg=yellow>{$added}</> resource(s) have been successfully published.");
    }
}
