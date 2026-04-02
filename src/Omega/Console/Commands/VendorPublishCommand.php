<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Omega\Console\Attribute\AsCommand;
use Omega\Support\AbstractServiceProvider;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(
    name: 'vendor:publish',
    description: 'Publish any publishable assets from vendor packages',
    options: [
        'tag'   => ['t', InputOption::VALUE_OPTIONAL, 'Specify the tag to run specific publishing', '*'],
        'force' => ['f', InputOption::VALUE_NONE, 'Overwrite existing files']
    ]
)]
final class VendorPublishCommand extends AbstractCommand
{
    public function __invoke(): int
    {
        $tag = $this->getOption('tag');
        $force = $this->getOption('force');

        $modules = AbstractServiceProvider::getModules();

        if (empty($modules)) {
            $this->io->warning('No publishable resources found.');
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
            $this->io->error("No publishable resources found for tag: {$targetTag}");
            return;
        }

        $this->io->info("Publishing resources...");

        $progressBar = new ProgressBar($this->output, count($filtered));
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

        $this->io->success("Done! <fg=yellow>{$added}</> resource(s) have been successfully published.");
    }
}
