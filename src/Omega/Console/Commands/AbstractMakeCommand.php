<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;

abstract class AbstractMakeCommand extends AbstractCommand
{
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
            $this->io->warning('File already exists');
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
            $this->io->error('Failed to write file');
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
}
