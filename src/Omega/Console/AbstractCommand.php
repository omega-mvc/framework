<?php

declare(strict_types=1);

namespace Omega\Console;

use Omega\Application\ApplicationInterface;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Psr\Container\ContainerExceptionInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractCommand extends Command
{
    /** @var InputInterface L'istanza di input corrente. */
    protected InputInterface $input;

    protected OutputInterface $output;

    /** @var SymfonyStyle L'istanza di output (con superpoteri grafici). */
    protected SymfonyStyle $io;

    protected ApplicationInterface $app;

    /**
     * Inietta l'istanza dell'applicazione Omega.
     */
    public function setApp(ApplicationInterface $app): void
    {
        $this->app = $app;
    }

    /**
     * Il metodo execute di Symfony viene "sigillato" qui.
     * Serve a preparare l'ambiente e delegare al metodo handle().
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input  = $input;
        $this->output = $output;
        $this->io     = new SymfonyStyle($input, $output);

        // Chiamiamo l'handle() definito nei comandi reali
        return (int) $this->handle();
    }

    /**
     * La logica del comando va scritta qui.
     *
     * @return int|void
     */
    abstract protected function handle();

    /**
     * Recupera il valore di un argomento.
     */
    protected function argument(string $key): mixed
    {
        return $this->input->getArgument($key);
    }

    /**
     * Recupera il valore di un'opzione.
     */
    protected function option(string $key): mixed
    {
        return $this->input->getOption($key);
    }

    // --- Helper Grafici (Stile Omega) ---

    protected function info(string $message): void
    {
        $this->io->info($message);
    }

    protected function error(string $message): void
    {
        $this->io->error($message);
    }

    protected function warn(string $message): void
    {
        $this->io->warning($message);
    }

    protected function comment(string $message): void
    {
        $this->io->comment($message);
    }

    protected function success(string $message): void
    {
        $this->io->success($message);
    }

    /**
     * Generate a file from a stub template.
     *
     * @param string $argument
     * @param array<string, string> $makeOption
     * @param string $folder
     */
    protected function makeTemplate(string $argument, array $makeOption, string $folder = ''): bool
    {
        $folder = ucfirst($folder);

        // Costruzione del percorso file (rimane invariata)
        $fileName = $makeOption['save_location']
            . $folder
            . $argument
            . $makeOption['suffix'];

        if (file_exists($fileName)) {
            $this->warn('File already exists');
            return false;
        }

        if ($folder !== '' && !is_dir($makeOption['save_location'] . $folder)) {
            mkdir($makeOption['save_location'] . $folder, 0755, true);
        }

        $template = file_get_contents($makeOption['template_location']);

        // 1. Sostituzione del pattern principale (es: __command__ -> HelloWorld)
        $template = str_replace(
            $makeOption['pattern'],
            $makeOption['replace'] ?? $argument,
            $template
        );

        // 2. 🔹 GESTIONE VARIABILI OPZIONALI (Solo se presenti in $makeOption['vars'])
        if (isset($makeOption['vars']) && is_array($makeOption['vars'])) {
            foreach ($makeOption['vars'] as $search => $replace) {
                $template = str_replace($search, $replace, $template);
            }
        }

        // Rimuove la prima riga (probabilmente per gestire gli stub con tag PHP o commenti di intestazione)
        $template = preg_replace('/^.+\n/', '', $template);

        $written = file_put_contents($fileName, $template);

        if ($written === false) {
            $this->error('Failed to write file');
            return false;
        }

        return true;
    }

    /**
     * Ensure that the given directory exists. Creates it recursively if missing.
     *
     * @param string $binding Logical path or container binding (e.g., "app.Http.Middlewares")
     * @return string Absolute filesystem path
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    protected function isPath(string $binding): string
    {
        $logicalPath = $this->app->get($binding);

        $realPath = str_replace(['.', '/','\\'], DIRECTORY_SEPARATOR, $logicalPath);

        if (!is_dir($realPath)) {
            mkdir($realPath, 0755, true);
        }

        return $realPath;
    }

    /**
     * Helper to find files recursively.
     */
    protected function findFiles(string $directory, string $pattern): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && fnmatch($pattern, $file->getFilename())) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    private function invalidAction(string $action): int
    {
        $this->error("Invalid action: {$action}. Use 'cache' or 'clear'.");
        return self::FAILURE;
    }
}
