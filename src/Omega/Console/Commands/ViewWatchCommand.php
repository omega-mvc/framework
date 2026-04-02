<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Omega\Console\Attribute\AsCommand;
use Omega\Text\Str;
use Omega\View\Templator;
use Symfony\Component\Console\Input\InputOption;
use Throwable;

#[AsCommand(
    name: 'view:watch',
    description: 'Watch view files and recompile them on change',
    options: [
        'prefix'=> ['p', InputOption::VALUE_REQUIRED, 'File pattern to watch', '*.php']
    ]
)]
final class ViewWatchCommand extends AbstractCommand
{
    use ViewCommandFilesTrait;

    private bool $shouldExit = false;
    private int $width = 80;

    /**
     * @return int
     */
    public function __invoke(): int
    {
        $this->io->warning('Watching view files in ' . $this->app->get('path.view') . '...');
        $this->io->info('Press CTRL+C to stop watching.');

        /** @var Templator $templator */
        $templator = $this->app[Templator::class];
        $prefix = $this->getOption('prefix');

        $getIndexes = $this->getIndexFiles($prefix);
        if (empty($getIndexes)) {
            return self::FAILURE;
        }

        $compiled = $this->precompile($templator, $getIndexes);

        while (!$this->shouldExit) {
            $reindex = false;

            foreach ($getIndexes as $file => $time) {
                clearstatcache(true, $file);

                if (!is_file($file)) {
                    $reindex = true;
                    continue;
                }

                $now = filemtime($file);

                if ($now > $time) {
                    $dependency = $this->compileSingle($templator, $file);

                    foreach ($dependency as $compile => $depTime) {
                        $compile = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $compile);
                        $compiled[$compile][$file] = $time;
                    }

                    $getIndexes[$file] = $now;
                    $reindex = true;

                    if (isset($compiled[$file])) {
                        foreach ($compiled[$file] as $parentFile => $parentTime) {
                            $this->compileSingle($templator, $parentFile);
                            $getIndexes[$parentFile] = $now;
                        }
                    }
                }
            }

            if ($reindex || count($getIndexes) !== count($newIndexes = $this->getIndexFiles($prefix))) {
                $getIndexes = $newIndexes ?? $getIndexes;
                $compiled = $this->precompile($templator, $getIndexes);
            }

            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }

            usleep(10_000);
        }

        return self::SUCCESS;
    }

    /**
     * Crea l'indice dei file con timestamp.
     */
    private function getIndexFiles(string $prefix): array
    {
        $files = $this->findFiles($this->app->get('path.view'), $prefix);
        $indexes = [];

        foreach ($files as $file) {
            if (is_file($file)) {
                $indexes[$file] = filemtime($file);
            }
        }

        arsort($indexes);

        return $indexes;
    }

    private function compileSingle(Templator $templator, string $filePath): array
    {
        $start = microtime(true);
        $viewPath = $this->app->get('path.view');
        $filename = Str::replace($filePath, $viewPath, '');

        try {
            $templator->compile($filename);
            $time = round((microtime(true) - $start) * 1000, 2);

            $dots = str_repeat('.', max(2, $this->width - strlen($filename) - 10));
            $this->io->text(sprintf(" <info>%s</info> <fg=gray>%s</> <comment>%s ms</comment>", $filename, $dots, $time));

            return $templator->getDependency($filePath);
        } catch (Throwable $e) {
            $this->io->error("Error compiling {$filename}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Fase iniziale di pre-compilazione.
     */
    private function precompile(Templator $templator, array $indexes): array
    {
        $compiledMap = [];
        $start = microtime(true);

        foreach ($indexes as $file => $time) {
            $filename = Str::replace($file, $this->app->get('path.view'), '');
            $templator->compile($filename);

            foreach ($templator->getDependency($file) as $depPath => $depTime) {
                $depPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $depPath);
                $compiledMap[$depPath][$file] = $time;
            }
        }

        $time = round((microtime(true) - $start) * 1000, 2);
        $this->io->text(sprintf("<fg=yellow;options=bold>PRE-COMPILE</> %s <comment>%s ms</comment>", str_repeat('.', 50), $time));

        return $compiledMap;
    }
}
