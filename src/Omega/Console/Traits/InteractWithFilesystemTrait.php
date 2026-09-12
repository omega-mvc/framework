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

namespace Omega\Console\Traits;

use function basename;
use function fnmatch;
use function is_dir;
use function is_file;
use function is_link;
use function realpath;
use function scandir;
use function sort;
use function str_starts_with;

use const DIRECTORY_SEPARATOR;

/**
 * Trait providing filesystem utilities for console commands or services.
 *
 * This trait allows searching for files recursively in a directory,
 * using one or more patterns and optional exclusions.
 * It is intended to be reusable in any context that requires
 * filesystem interaction without coupling to specific commands.
 *
 * Example usage:
 * ```
 * $files = $this->findFiles('/path/to/dir', ['*.php', '*.twig'], ['Test*.php']);
 * ```
 *
 * @category   Omega
 * @package    Console
 * @subpackage Traits
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
trait InteractWithFilesystemTrait
{
    /**
     * Recursively searches for files in a directory matching given patterns.
     *
     * Matching is performed against the file name with fnmatch() glob patterns,
     * mirroring the previous Symfony Finder based behavior without the external
     * dependency. Hidden files and symlinks are ignored, and results are sorted
     * by path for deterministic output.
     *
     * @param string               $directory Directory to search in.
     * @param string|string[]      $patterns  A pattern or an array of patterns to match (e.g., '*.php').
     * @param string[]             $exclude   An array of patterns to exclude (e.g., ['Test*.php']).
     * @return array<int,string>             An array of absolute paths of the matched files.
     */
    protected function findFiles(string $directory, string|array $patterns = '*', array $exclude = []): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $patterns = (array) $patterns;
        $files    = [];

        foreach ($this->collectFiles($directory) as $path) {
            if (!is_file($path)) {
                continue;
            }

            $name = basename($path);

            if (str_starts_with($name, '.')) {
                continue;
            }

            if ($this->matchesAnyPattern($name, $exclude)) {
                continue;
            }

            if ($this->matchesAnyPattern($name, $patterns)) {
                $files[] = realpath($path) ?: $path;
            }
        }

        sort($files);

        return $files;
    }

    /**
     * Recursively collect the absolute paths of every file in a directory subtree.
     *
     * Symlinked entries are skipped to avoid infinite recursion, mirroring the
     * previous finder behavior of not following links.
     *
     * @param string $directory Directory to scan.
     * @return list<string> Absolute paths of every file in the subtree.
     */
    private function collectFiles(string $directory): array
    {
        $files = [];

        foreach (scandir($directory) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $entry;

            if (is_link($path)) {
                continue;
            }

            if (is_dir($path)) {
                $files = [...$files, ...$this->collectFiles($path)];
            } elseif (is_file($path)) {
                $files[] = $path;
            }
        }

        return $files;
    }

    /**
     * Check whether a file name matches any of the given glob patterns.
     *
     * @param string   $name     File name to test.
     * @param string[] $patterns Glob patterns to match against.
     * @return bool True when at least one pattern matches.
     */
    private function matchesAnyPattern(string $name, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (fnmatch($pattern, $name)) {
                return true;
            }
        }

        return false;
    }
}
