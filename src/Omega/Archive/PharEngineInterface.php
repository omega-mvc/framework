<?php

/**
 * Part of Omega MVC - Archive Package
 * php version 8.4
 *
 * @link        https://omegamvc.github.io
 * @author      Adriano Giovannini <agisoftt@gmail.com>
 * @copyright   Copyright (c) 2024 - 2025 Adriano Giovannini. (https://omegamvc.github.io)
 * @license     https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version     1.0.0
 */

declare(strict_types=1);

namespace Omega\Archive;

/**
 * PharEngineInterface.
 *
 * Abstracts the low-level operations required to access the contents of a Phar
 * archive. The {@see PharAdapter} depends on this interface so that the Phar
 * extension can be swapped for an in-memory fake during testing, allowing the
 * write, delete, and rename paths to be exercised even when `phar.readonly=1`
 * prevents real PHAR writes.
 *
 * @category    Omega
 * @package     Archive
 * @link        https://omegamvc.github.io
 * @author      Adriano Giovannini <agisoftt@gmail.com>
 * @copyright   Copyright (c) 2024 - 2025 Adriano Giovannini. (https://omegamvc.github.io)
 * @license     https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version     1.0.0
 */
interface PharEngineInterface
{
    /**
     * Checks whether a member with the given key exists in the archive.
     *
     * @param string $key Holds the key to check.
     * @return bool True if the member exists, false otherwise.
     */
    public function contains(string $key): bool;

    /**
     * Reads the content of a member.
     *
     * @param string $key Holds the key of the member to read.
     * @return string|false The member content, or false if it cannot be read.
     */
    public function read(string $key): string|false;

    /**
     * Writes content to the given member.
     *
     * @param string $key     Holds the key to write.
     * @param string $content Holds the content to write.
     * @return void
     */
    public function write(string $key, string $content): void;

    /**
     * Removes a member from the archive.
     *
     * @param string $key Holds the key to delete.
     * @return void
     */
    public function delete(string $key): void;

    /**
     * Returns all member keys present in the archive.
     *
     * @return string[] Return an array of all member keys.
     */
    public function keys(): array;

    /**
     * Checks whether a member is a directory.
     *
     * @param string $key Holds the key to check.
     * @return bool True if the member is a directory, false otherwise.
     */
    public function isDirectory(string $key): bool;

    /**
     * Returns the last modification timestamp of a member.
     *
     * @param string $key Holds the key to check.
     * @return int The last modification timestamp.
     */
    public function mtime(string $key): int;
}
