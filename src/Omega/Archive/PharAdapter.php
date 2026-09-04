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

use Phar;
use RuntimeException;

use function file_exists;
use function strlen;

/**
 * PharAdapter class.
 *
 * A class that implements the `AdapterInterface' for handling Phar (PHP Archive) files.
 * It allows operations like opening, reading, writing, deleting, and renaming files in a Phar archive.
 * The class utilizes the Phar extension to manipulate the contents of Phar archives.
 * Some methods such as deleting files and renaming rely on the Phar extension's capabilities.
 * It does not require manual closing, as the Phar class manages it automatically.
 *
 * All access to the underlying archive is delegated to a {@see PharEngineInterface}, which
 * is injectable so that the write, delete, and rename paths remain testable even
 * when the environment disables PHAR writes via `phar.readonly=1`.
 *
 * @category    Omega
 * @package     Archive
 * @link        https://omegamvc.github.io
 * @author      Adriano Giovannini <agisoftt@gmail.com>
 * @copyright   Copyright (c) 2024 - 2025 Adriano Giovannini. (https://omegamvc.github.io)
 * @license     https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version     1.0.0
 */
class PharAdapter implements AdapterInterface
{
    /**
     * PharEngineInterface instance.
     *
     * @var PharEngineInterface Holds an instance of PharEngineInterface.
     */
    protected PharEngineInterface $engine;

    /**
     * PharAdapter constructor.
     *
     * Initializes the PharAdapter with the given path to the Phar archive file.
     * A PharEngineInterface (by default backed by a native Phar instance and associated with
     * the archive file) is created for managing the contents.
     *
     * @param string      $pharFile The path to the Phar file to be used for the archive operations.
     * @param PharEngineInterface|null $engine  An optional PharEngineInterface to use instead of the native one.
     * @return void
     * @throws RuntimeException if the Phar file not exists.
     */
    public function __construct(
        protected string $pharFile,
        ?PharEngineInterface $engine = null
    ) {
        if ($engine === null) {
            if (!file_exists($pharFile)) {
                throw new RuntimeException("The Phar file '$pharFile' does not exist.");
            }

            $this->engine = new NativePharEngine(new Phar($pharFile));
        } else {
            $this->engine = $engine;
        }
    }

    /**
     * {@inheritdoc}
     * @throws RuntimeException if the Phar file not exists or failed to open the archive.
     */
    public function open(string $file): void
    {
        if (!file_exists($file)) {
            throw new RuntimeException("The Phar file '$file' does not exist.");
        }

        try {
            $this->engine = new NativePharEngine(new Phar($file));
        } catch (RuntimeException $e) {
            throw new RuntimeException("Failed to open Phar archive '$file'. " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function close(): void
    {
    }

    /**
     * {@inheritdoc}
     * @throws RuntimeException if the key not exists in Phar archive.
     */
    public function read(string $key): string|bool
    {
        if (!$this->engine->contains($key)) {
            throw new RuntimeException("The key '$key' does not exist in the Phar archive.");
        }

        return $this->engine->read($key);
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $key, string $content): int|bool
    {
        $this->engine->write($key, $content);

        return strlen($content);
    }

    /**
     * {@inheritdoc}
     * @throws RuntimeException if the key not exists or failed to delete the key.
     */
    public function delete(string $key): bool
    {
        if (!$this->engine->contains($key)) {
            throw new RuntimeException("The key '$key' does not exist and cannot be deleted.");
        }

        $this->engine->delete($key);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $key): bool
    {
        return $this->engine->contains($key);
    }

    /**
     * {@inheritdoc}
     */
    public function keys(): array
    {
        return $this->engine->keys();
    }

    /**
     * {@inheritdoc}
     * @throws RuntimeException if the key not exists.
     */
    public function isDirectory(string $key): bool
    {
        if (!$this->engine->contains($key)) {
            throw new RuntimeException("The key '$key' does not exist.");
        }

        return $this->engine->isDirectory($key);
    }

    /**
     * {@inheritdoc}
     * @throws RuntimeException if the key not exists.
     */
    public function mtime(string $key): int|bool
    {
        if (!$this->engine->contains($key)) {
            throw new RuntimeException("The key '$key' does not exist.");
        }

        return $this->engine->mtime($key);
    }

    /**
     * {@inheritdoc}
     * @throws RuntimeException if the renaming operation fails due to file not existing, target file existing, or
     *                          any other failure.
     */
    public function rename(string $sourceKey, string $targetKey): bool
    {
        if (!$this->engine->contains($sourceKey)) {
            throw new RuntimeException("Source file '$sourceKey' does not exist.");
        }

        if ($this->engine->contains($targetKey)) {
            throw new RuntimeException("Target file '$targetKey' already exists.");
        }

        try {
            $content = $this->engine->read($sourceKey);

            if ($content === false) {
                throw new RuntimeException("Failed to read content from '$sourceKey' before renaming.");
            }

            $this->engine->write($targetKey, $content);
            $this->engine->delete($sourceKey);
        } catch (RuntimeException $e) {
            throw new RuntimeException("Failed to rename '$sourceKey' to '$targetKey'. " . $e->getMessage(), 0, $e);
        }

        return true;
    }
}
