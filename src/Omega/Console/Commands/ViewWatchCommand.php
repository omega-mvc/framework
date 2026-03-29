<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;
use Omega\View\Templator;
use Omega\Text\Str;
use Throwable;

use function Omega\Support\get_path;

#[AsCommand(
    name: 'view:watch',
    description: 'Watch view files and recompile them on change'
)]
final class ViewWatchCommand extends AbstractCommand
{
    private bool $shouldExit = false;
    private int $width = 80;

    protected function configure(): void
    {
        $this->addOption('prefix', 'p', InputOption::VALUE_REQUIRED, 'File pattern to watch', '*.php');
    }

    /**
     * @return int
     */
    protected function handle(): int
    {
        $this->warn('Watching view files in ' . get_path('path.view') . '...');
        $this->info('Press CTRL+C to stop watching.');

        /** @var Templator $templator */
        $templator = $this->app[Templator::class];
        $prefix = $this->option('prefix');

        // Inizializzazione indici
        $getIndexes = $this->getIndexFiles($prefix);
        if (empty($getIndexes)) {
            return self::FAILURE;
        }

        // Pre-compilazione e mapping dipendenze
        $compiled = $this->precompile($templator, $getIndexes);

        // Loop di monitoraggio
        while (!$this->shouldExit) {
            $reindex = false;

            foreach ($getIndexes as $file => $time) {
                clearstatcache(true, $file);

                if (!is_file($file)) {
                    $reindex = true;
                    continue;
                }

                $now = filemtime($file);

                // Se il file è stato modificato
                if ($now > $time) {
                    $dependency = $this->compileSingle($templator, $file);

                    // Aggiorna mappa dipendenze
                    foreach ($dependency as $compile => $depTime) {
                        $compile = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $compile);
                        $compiled[$compile][$file] = $time;
                    }

                    $getIndexes[$file] = $now;
                    $reindex = true;

                    // Ricompila i file dipendenti (padri)
                    if (isset($compiled[$file])) {
                        foreach ($compiled[$file] as $parentFile => $parentTime) {
                            $this->compileSingle($templator, $parentFile);
                            $getIndexes[$parentFile] = $now;
                        }
                    }
                }
            }

            // Se sono stati aggiunti o rimossi file, rifacciamo l'indice
            if ($reindex || count($getIndexes) !== count($newIndexes = $this->getIndexFiles($prefix))) {
                $getIndexes = $newIndexes ?? $getIndexes;
                $compiled = $this->precompile($templator, $getIndexes);
            }

            // Gestione segnali (se disponibile)
            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }

            // Sleep per non saturare la CPU (10ms è un buon compromesso tra reattività e consumo)
            usleep(10_000);
        }

        return self::SUCCESS;
    }

    /**
     * Crea l'indice dei file con timestamp.
     */
    private function getIndexFiles(string $prefix): array
    {
        $files = $this->findFiles(get_path('path.view'), $prefix);
        $indexes = [];

        foreach ($files as $file) {
            if (is_file($file)) {
                $indexes[$file] = filemtime($file);
            }
        }

        arsort($indexes);
        return $indexes;
    }

    /**
     * Compila un singolo file e logga l'esecuzione.
     */
    private function compileSingle(Templator $templator, string $filePath): array
    {
        $start = microtime(true);
        $viewPath = get_path('path.view');
        $filename = Str::replace($filePath, $viewPath, '');

        try {
            $templator->compile($filename);
            $time = round((microtime(true) - $start) * 1000, 2);

            // Layout stile Omega: Nome file ............ 12ms
            $dots = str_repeat('.', max(2, $this->width - strlen($filename) - 10));
            $this->io->text(sprintf(" <info>%s</info> <fg=gray>%s</> <comment>%s ms</comment>", $filename, $dots, $time));

            return $templator->getDependency($filePath);
        } catch (Throwable $e) {
            $this->error("Error compiling {$filename}: " . $e->getMessage());
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
            $filename = Str::replace($file, get_path('path.view'), '');
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
