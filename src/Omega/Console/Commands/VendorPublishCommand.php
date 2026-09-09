<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Omega\Console\Attribute\AsCommand;
use Omega\Container\AbstractServiceProvider;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputOption;

use function array_filter;
use function count;
use function is_dir;
use function is_string;

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
        $tag = is_string($this->getOption('tag')) ? $this->getOption('tag') : '*';
        $force = (bool) $this->getOption('force');

        /** @var array<string, array<string, string>> $modules */
        $modules = AbstractServiceProvider::getModules();

        if (empty($modules)) {
            $this->io->warning('No publishable resources found.');
            return self::SUCCESS;
        }

        $published = $this->publishItems($modules, $tag, $force);

        return $published > 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Handles the publication of modules filtered by tag.
     *
     * @param array<string, array<string, string>> $modules
     */
    private function publishItems(array $modules, string $targetTag, bool $force): int
    {
        $added = 0;

        // Filter modules by tag
        $filtered = ($targetTag === '*')
            ? $modules
            : array_filter($modules, fn(string $tag): bool => $tag === $targetTag, ARRAY_FILTER_USE_KEY);

        if (empty($filtered)) {
            $this->io->error("No publishable resources found for tag: {$targetTag}");
            return 0;
        }

        $this->io->info('Publishing resources...');

        $progressBar = new ProgressBar($this->output, count($filtered));
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% -- %message%');
        $progressBar->start();

        foreach ($filtered as $tag => $module) {
            foreach ($module as $from => $to) {
                $progressBar->setMessage("Publishing resources for tag: <comment>{$tag}</comment>");

                // Qui il metodo del provider rimane import per ora
                $success = is_dir($from)
                    ? AbstractServiceProvider::importDir($from, $to, $force)
                    : AbstractServiceProvider::importFile($from, $to, $force);

                if ($success) {
                    $added++;
                }
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->io->newLine(2);

        $this->io->info("Done! <fg=yellow>{$added}</> resource(s) have been successfully published.");

        return $added;
    }
}
