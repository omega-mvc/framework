<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Omega\Console\Attribute\Make;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Text\Str;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionException;

use function Omega\Application\path;
use function array_map;
use function file_get_contents;
use function file_put_contents;
use function is_dir;
use function is_string;
use function mkdir;
use function preg_replace;
use function rtrim;
use function str_replace;

abstract class AbstractMakeCommand extends AbstractCommand
{
    /**
     * @return int
     * @throws BindingResolutionException
     * @throws CircularAliasException
     * @throws EntryNotFoundException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function __invoke(): int
    {
        $name = $this->getArgument('name');

        if (!is_string($name)) {
            $this->io->error('The "name" argument must be a string.');
            return self::FAILURE;
        }

        $reflection = new ReflectionClass($this);
        $attribute = $reflection->getAttributes(Make::class)[0] ?? null;

        if (!$attribute) {
            $this->io->error('Missing #[Make] attribute.');
            return self::FAILURE;
        }

        $config = $attribute->newInstance();

        $savePath = $this->app->get($config->path);

        if (!is_string($savePath)) {
            $this->io->error("The \"{$config->path}\" binding must resolve to a string path.");
            return self::FAILURE;
        }

        $success = $this->makeTemplate($name, [
            'template_location' => $config->template,
            'save_location'     => $savePath,
            'pattern'           => $config->pattern,
            'suffix'            => $config->suffix,
            'vars'              => $this->resolveVars($config->vars, $name),
        ]);

        if (!$success) {
            $this->warning($config, $name);
            return self::FAILURE;
        }

        $this->info($config, $name);

        return self::SUCCESS;
    }
/**
     * Resolve the template variables against the given name.
     *
     * @param array<string, string> $vars Template variables [placeholder => transformation].
     * @param string $name The generated class/file name.
     * @return array<string, string> The resolved variables keyed by placeholder.
     */
    protected function resolveVars(array $vars, string $name): array
    {
        return array_map(function (string $value) use ($name): string {
            return match ($value) {
                'kebab' => Str::toKebabCase($name),
                'snake' => Str::toSnakeCase($name),
                default => $value,
            };
        }, $vars);
    }

    /**
     * Generate a file from the configured stub template.
     *
     * @param string $argument The file base name without suffix.
     * @param array{template_location: string, save_location: string, pattern: string, suffix: string, vars: array<string, string>} $makeOption Resolved Make attribute options.
     * @param string $folder Optional sub-folder appended to the save path.
     * @return bool True on success, false when the file exists or an error occurs.
     */
    protected function makeTemplate(string $argument, array $makeOption, string $folder = ''): bool
    {
        $folder = $folder ? ucfirst($folder) . DIRECTORY_SEPARATOR : '';

        $basePath = rtrim($makeOption['save_location'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $targetDir = $basePath . $folder;

        $fileName = $targetDir . $argument . $makeOption['suffix'];

        if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
            $this->io->error("Unable to create directory [$targetDir]");
            return false;
        }

        if (file_exists($fileName)) {
            return false;
        }

        $template = file_get_contents($makeOption['template_location']);

        if ($template === false) {
            $this->io->error('Unable to read stub file');
            return false;
        }

        $template = str_replace(
            $makeOption['pattern'],
            $argument,
            $template
        );

        if (!empty($makeOption['vars'])) {
            foreach ($makeOption['vars'] as $search => $replace) {
                $template = str_replace($search, $replace, $template);
            }
        }

        $template = preg_replace('/^#!.*\n/', '', $template);

        if (file_put_contents($fileName, $template) === false) {
            $this->io->error("Failed to write file [$fileName]");
            return false;
        }

        return true;
    }

    /**
     * Print the success message configured in the Make attribute.
     *
     * @param Make $config The Make attribute instance.
     * @param string $name The generated file base name.
     * @return void
     */
    protected function info(Make $config, string $name): void
    {
        $location = path($config->target);
        $fileName = $name . $config->suffix;
        $fullPath = $location . $fileName;

        $message = str_replace(
            '__file__name__',
            $fullPath,
            $config->info
        );

        $this->io->info($message);
    }

    /**
     * Print the warning message configured in the Make attribute.
     *
     * @param Make $config The Make attribute instance.
     * @param string $name The generated file base name.
     * @return void
     */
    protected function warning(Make $config, string $name): void
    {
        $location = path($config->target);
        $fileName = $name . $config->suffix;
        $fullPath = $location . $fileName;

        $message = str_replace(
            '__file__name__',
            $fullPath,
            $config->warning
        );

        $this->io->warning($message);
    }
}
