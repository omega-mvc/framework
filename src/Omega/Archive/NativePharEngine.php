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
use PharData;

use function array_keys;
use function file_get_contents;
use function iterator_to_array;

/**
 * NativePharEngine class.
 *
 * Default {@see PharEngineInterface} implementation that wraps a native {@see Phar}
 * or {@see PharData} archive and exposes its operations through the engine interface.
 *
 * @category    Omega
 * @package     Archive
 * @link        https://omegamvc.github.io
 * @author      Adriano Giovannini <agisoftt@gmail.com>
 * @copyright   Copyright (c) 2024 - 2025 Adriano Giovannini. (https://omegamvc.github.io)
 * @license     https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version     1.0.0
 */
final class NativePharEngine implements PharEngineInterface
{
    /**
     * NativePharEngine constructor.
     *
     * @param Phar|PharData $phar Holds the underlying archive instance.
     * @return void
     */
    public function __construct(
        protected Phar|PharData $phar
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function contains(string $key): bool
    {
        return isset($this->phar[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function read(string $key): string|false
    {
        return file_get_contents($this->phar[$key]->getPathname());
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $key, string $content): void
    {
        $this->phar[$key] = $content;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): void
    {
        unset($this->phar[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function keys(): array
    {
        return array_keys(iterator_to_array($this->phar));
    }

    /**
     * {@inheritdoc}
     */
    public function isDirectory(string $key): bool
    {
        return $this->phar[$key]->isDir();
    }

    /**
     * {@inheritdoc}
     */
    public function mtime(string $key): int
    {
        return $this->phar[$key]->getMTime();
    }
}
