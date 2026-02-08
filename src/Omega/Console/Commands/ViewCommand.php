<?php

/**
 * Part of Omega - Console Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Console\Commands;

use Exception;
use Omega\Console\AbstractCommand;
use Omega\Console\Style\Decorate;
use Omega\Console\Style\ProgressBar;
use Omega\Console\Traits\PrintHelpTrait;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Text\Str;
use Omega\View\Templator;
use Psr\Container\ContainerExceptionInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionException;

use function array_key_exists;
use function arsort;
use function count;
use function fnmatch;
use function function_exists;
use function is_file;
use function microtime;
use function Omega\Console\exit_prompt;
use function Omega\Console\info;
use function Omega\Console\style;
use function Omega\Console\success;
use function Omega\Console\warn;
use function pcntl_signal_dispatch;
use function round;
use function str_replace;
use function strlen;
use function unlink;
use function usleep;

use const DIRECTORY_SEPARATOR;

/**
 * Console command for managing view templates.
 *
 * Provides utilities to compile, cache, clear, and watch view files.
 * It supports bulk compilation, cache cleanup, and a watch mode that
 * automatically recompiles templates when changes are detected.
 *
 * @category   Omega
 * @package    Console
 * @subpackage Commands
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 *
 * @property string|null $prefix
 */
class ViewCommand extends AbstractCommand
{
    use PrintHelpTrait;

    /**
     * Command registration configuration.
     *
     * Defines the pattern used to invoke the command and the method to execute.
     *
     * @var array<int, array<string, mixed>>
     */
    public static array $command = [
        [
            'pattern' => 'view:cache',
            'fn'      => [ViewCommand::class, 'cache'],
            'default' => [
                'prefix' => '*.php',
            ],
        ], [
            'pattern' => 'view:clear',
            'fn'      => [ViewCommand::class, 'clear'],
            'default' => [
                'prefix' => '*.php',
            ],
        ], [
            'pattern' => 'view:watch',
            'fn'      => [ViewCommand::class, 'watch'],
            'default' => [
                'prefix' => '*.php',
            ],
        ],
    ];

    /**
     * Returns a description of the command, its options, and their relations.
     *
     * This is used to generate help output for users.
     *
     * @return array<string, array<string, string|string[]>>
     */
    public function printHelp(): array
    {
        return [
            'commands'  => [
                'view:cache' => 'Create all templator template (optimize)',
                'view:clear' => 'Clear all cached view file',
                'view:watch' => 'Watch all view file',
            ],
            'options'   => [
                '--prefix' => 'Finding file by pattern given',
            ],
            'relation'  => [
                'view:cache' => ['--prefix'],
                'view:clear' => ['--prefix'],
                'view:watch' => ['--prefix'],
            ],
        ];
    }

    /**
     * Recursively scans a directory and returns all files matching a given pattern.
     *
     * @param string $directory Base directory to search in.
     * @param string $pattern   Filename pattern (glob-style) used for filtering.
     * @return array<string>    List of matched file paths.
     */

    private function findFiles(string $directory, string $pattern): array
    {
        $files    = [];
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

    /**
     * Compiles all view templates and stores them in the cache.
     *
     * @param Templator $templator View templating engine instance.
     * @return int Returns 0 on success, or 1 if no files are found.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception Thrown when a compilation error occurs.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function cache(Templator $templator): int
    {
        $files = $this->findFiles(get_path('path.view'), $this->prefix);
        if ([] === $files) {
            return 1;
        }
        info('build compiler cache')->out(false);
        $count     = 0;
        $progress = new ProgressBar(':progress :percent - :current', [
            ':current' => fn ($current, $max): string => array_key_exists($current, $files)
                ? Str::replace($files[$current], get_path('path.view'), '')
                : '',
        ]);

        $progress->mask = count($files);
        $watchStart     = microtime(true);
        foreach ($files as $file) {
            if (is_file($file)) {
                $filename = Str::replace($file, get_path('path.view'), '');
                $templator->compile($filename);
                $count++;
            }
            $progress->current++;
            $time               = round(microtime(true) - $watchStart, 3) * 1000;
            $progress->complete = static fn (): string => (string) success(
                "Success, $count file compiled ($time ms)."
            );
            $progress->tick();
        }

        return 0;
    }

    /**
     * Removes all cached view files.
     *
     * @return int Returns 0 when cache is cleared, or 1 if no cached files exist.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function clear(): int
    {
        warn('Clear cache file in ' . get_path('path.compiled_view_path'))->out(false);
        $files = $this->findFiles(get_path('path.compiled_view_path'), $this->prefix);
        //echo $files;
        if (0 === count($files)) {
            warn('No file cache clear.')->out();

            return 1;
        }

        $count     = 0;
        $progress = new ProgressBar(':progress :percent - :current', [
            ':current' => fn ($current, $max): string => array_key_exists($current, $files)
                ? Str::replace($files[$current], get_path('path.compiled_view_path'), '')
                : '',
        ]);

        $progress->mask = count($files);
        $watchStart     = microtime(true);
        foreach ($files as $file) {
            if (is_file($file)) {
                $count += unlink($file) ? 1 : 0;
            }
            $progress->current++;
            $time                = round(microtime(true) - $watchStart, 3) * 1000;
            $progress->complete = static fn (): string => (string) success("Success, $count cache clear ($time ms).");
            $progress->tick();
        }

        return 0;
    }

    /**
     * Watches view files and recompiles them when changes are detected.
     *
     * @param Templator $templator View templating engine instance.
     * @return int Returns 0 when the watch process ends, or 1 on initialization failure.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception Thrown when a compilation or watch error occurs.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function watch(Templator $templator): int
    {
        warn('Clear cache file in ' . get_path('path.view') . $this->prefix)->out(false);

        $width      = $this->getWidth(40, 80);
        $signal     = false;
        $getIndexes = $this->getIndexFiles();
        if ([] === $getIndexes) {
            return 1;
        }

        // register ctrl+c
        exit_prompt('Press any key to stop watching', [
            'yes' => static function () use (&$signal) {
                $signal = true;
            },
        ]);

        // precompile
        $compiled = $this->precompile($templator, $getIndexes, $width);

        // watch file change until signal
        do {
            $reindex = false;
            foreach ($getIndexes as $file => $time) {
                clearstatcache(true, $file);
                $now = filemtime($file);

                // compile only newest file
                if ($now > $time) {
                    $dependency = $this->compile($templator, $file, $width);
                    foreach ($dependency as $compile => $depTime) {
                        $compile                   = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $compile);
                        $compiled[$compile][$file] = $time;
                    }
                    $getIndexes[$file] = $now;
                    $reindex            = true;

                    // recompile dependent
                    if (isset($compiled[$file])) {
                        foreach ($compiled[$file] as $compile => $deepTime) {
                            $this->compile($templator, $compile, $width);
                            $getIndexes[$compile] = $now;
                        }
                    }
                }
            }

            // reindexing
            if (count($getIndexes) !== count($newIndexes = $this->getIndexFiles())) {
                $getIndexes = $newIndexes;
                $compiled   = $this->precompile($templator, $getIndexes, $width);
            }
            if ($reindex) {
                asort($getIndexes);
            }

            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }

            usleep(1_000); // 1ms
        } while (!$signal);

        return 0;
    }

    /**
     * Builds an index of view files with their last modification times.
     *
     * @return array<string,int> An associative array of file paths and timestamps.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    private function getIndexFiles(): array
    {
        $files = $this->findFiles(get_path('path.view'), $this->prefix);

        if (empty($files)) {
            warn('Error finding view file(s).')->out();

            return [];
        }

        // indexing files (time modified)
        $indexes = [];
        foreach ($files as $file) {
            if (false === is_file($file)) {
                continue;
            }

            $indexes[$file] = filemtime($file);
        }

        // sort for newest file
        arsort($indexes);

        return $indexes;
    }

    /**
     * Compiles a single view file and outputs its execution time.
     *
     * @param Templator $templator View templating engine instance.
     * @param string    $filePath  Absolute path to the view file.
     * @param int       $width     Output formatting width.
     * @return array<string,int>   Dependency list with modification times.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception Thrown when compilation fails.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    private function compile(Templator $templator, string $filePath, int $width): array
    {
        $watchStart        = microtime(true);
        $filename          = Str::replace($filePath, get_path('path.view'), '');
        $templator->compile($filename);
        $length            = strlen($filename);
        $executeTime       = round(microtime(true) - $watchStart, 3) * 1000;
        $executeTimeLength = strlen((string) $executeTime);

        style($filename)
            ->repeat('.', $width - $length - $executeTimeLength - 2)->textDim()
            ->push((string) $executeTime)
            ->push('ms')->textYellow()
            ->out();

        return $templator->getDependency($filePath);
    }

    /**
     * Precompiles all indexed view files and resolves their dependencies.
     *
     * @param Templator          $templator View templating engine instance.
     * @param array<string,int>  $getIndexes Indexed view files with timestamps.
     * @param int                $width Output formatting width.
     * @return array<string,array<string,int>> Compiled dependency map.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws Exception Thrown when precompilation fails.
     */
    private function precompile(Templator $templator, array $getIndexes, int $width): array
    {
        $compiled       = [];
        $watchStart     = microtime(true);
        foreach ($getIndexes as $file => $time) {
            $filename        = Str::replace($file, get_path('path.view'), '');
            $templator->compile($filename);
            foreach ($templator->getDependency($file) as $compile => $compiledTime) {
                $compile                   = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $compile);
                $compiled[$compile][$file] = $time;
            }
        }
        $executeTime       = round(microtime(true) - $watchStart, 3) * 1000;
        $executeTimeLength = strlen((string) $executeTime);
        style('PRE-COMPILE')
            ->bold()->rawReset([Decorate::RESET])->textYellow()
            ->repeat('.', $width - $executeTimeLength - 13)->textDim()
            ->push((string) $executeTime)
            ->push('ms')->textYellow()
            ->out();

        return $compiled;
    }
}
