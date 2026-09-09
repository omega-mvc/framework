<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Omega\Console\Attribute\AsCommand;
use Omega\Console\Traits\InteractWithFilesystemTrait;
use Omega\Text\Str;
use Omega\View\Templator;
use Symfony\Component\Console\Input\InputOption;
use Throwable;

use function Omega\Application\os_detect;
use function clearstatcache;
use function count;
use function filemtime;
use function function_exists;
use function is_file;
use function is_string;
use function microtime;
use function pcntl_async_signals;
use function pcntl_signal;
use function pcntl_signal_dispatch;
use function round;
use function sprintf;
use function str_repeat;
use function str_replace;
use function strlen;
use function usleep;

use const DIRECTORY_SEPARATOR;

#[AsCommand(
    name: 'view:watch',
    description: 'Watch view files and recompile them on change',
    options: [
        'prefix' => ['p', InputOption::VALUE_REQUIRED, 'File pattern to watch', '*.php']
    ]
)]
final class ViewWatchCommand extends AbstractCommand
{
    use InteractWithFilesystemTrait;

    private bool $shouldExit = false;
    private int $width = 80;
    private string $viewPath = '';

    /**
     * @return int
     */
    public function __invoke(): int
    {
        $viewPath = $this->app->get('path.view');
        if (!is_string($viewPath)) {
            $this->io->error('The "path.view" binding must resolve to a string path.');
            return self::FAILURE;
        }

        $this->viewPath = $viewPath;

        $this->io->info('Watching view files in ' . '<options=bold>' . $this->viewPath . '</>');
        $this->io->info('Press CTRL+C to stop watching.');

        if (os_detect() !== 'windows' && function_exists('pcntl_async_signals')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGINT, function (): void {
                $this->shouldExit = true;
            });
        }

        /** @var Templator $templator */
        $templator = $this->app->get(Templator::class);
        $prefix = $this->getOption('prefix');
        if (!is_string($prefix)) {
            $prefix = '*.php';
        }

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

                if (false === $now) {
                    continue;
                }

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

            $newIndexes = $this->getIndexFiles($prefix);

            if ($reindex || count($getIndexes) !== count($newIndexes)) {
                $getIndexes = $newIndexes;
                $compiled = $this->precompile($templator, $getIndexes);
            }

            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }

            usleep(1_000_000);
        }

        return self::SUCCESS;
    }

    /**
     * Builds the index of view files with their timestamps.
     *
     * @param string $prefix The file pattern to watch.
     * @return array<string, int> Map of view file paths to their last modification timestamps.
     */
    private function getIndexFiles(string $prefix): array
    {
        $files = $this->findFiles($this->viewPath, $prefix);
        $indexes = [];

        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }

            $time = filemtime($file);

            if (false !== $time) {
                $indexes[$file] = $time;
            }
        }

        arsort($indexes);

        return $indexes;
    }

    /**
     * Compile a single view file and return its dependency map.
     *
     * @param Templator $templator The templator instance to compile with.
     * @param string    $filePath  Absolute path of the view file.
     * @return array<string, int> Map of dependent file paths to their timestamps.
     */
    private function compileSingle(Templator $templator, string $filePath): array
    {
        $start = microtime(true);
        $filename = Str::replace($filePath, $this->viewPath, '');

        try {
            $templator->compile($filename);
            $time = round((microtime(true) - $start) * 1000, 2);

            $dots = str_repeat('.', max(2, $this->width - strlen($filename) - 10));
            $this->io->text(sprintf(
                " <info>%s</info> <fg=gray>%s</> <comment>%s ms</comment>",
                $filename,
                $dots,
                $time
            ));

            return $templator->getDependency($filePath);
        } catch (Throwable $e) {
            $this->io->error("Error compiling {$filename}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Pre-compiles all indexed views at startup.
     *
     * @param Templator         $templator The templator instance to compile with.
     * @param array<string, int> $indexes   Map of view file paths to their timestamps.
     * @return array<string, array<string, int>> Map of dependency paths to parent index mappings.
     */
    private function precompile(Templator $templator, array $indexes): array
    {
        $compiledMap = [];
        $start = microtime(true);

        foreach ($indexes as $file => $time) {
            $filename = Str::replace($file, $this->viewPath, '');
            $templator->compile($filename);

            foreach ($templator->getDependency($file) as $depPath => $depTime) {
                $depPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $depPath);
                $compiledMap[$depPath][$file] = $time;
            }
        }

        $time = round((microtime(true) - $start) * 1000, 2);
        $this->io->text(sprintf(
            "<fg=yellow;options=bold>PRE-COMPILE</> %s <comment>%s ms</comment>",
            str_repeat('.', 50),
            $time
        ));

        return $compiledMap;
    }
}
