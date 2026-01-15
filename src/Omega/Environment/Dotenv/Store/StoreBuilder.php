<?php

declare(strict_types=1);

namespace Omega\Environment\Dotenv\Store;

use Omega\Environment\Dotenv\Store\File\Paths;

use function array_merge;

readonly class StoreBuilder
{
    /**
     * The of default name.
     */
    private const string DEFAULT_NAME = '.env';

    /**
     * Create a new store builder instance.
     *
     * @param string[]    $paths
     * @param string[]    $names
     * @param bool        $shortCircuit
     * @param string|null $fileEncoding
     *
     * @return void
     */
    private function __construct(
        private array $paths = [],
        private array $names = [],
        private bool $shortCircuit = false,
        private ?string $fileEncoding = null
    ) {
    }

    /**
     * Create a new store builder instance with no names.
     *
     * @return StoreBuilder
     */
    public static function createWithNoNames(): StoreBuilder
    {
        return new self();
    }

    /**
     * Create a new store builder instance with the default name.
     *
     * @return StoreBuilder
     */
    public static function createWithDefaultName(): StoreBuilder
    {
        return new self([], [self::DEFAULT_NAME]);
    }

    /**
     * Creates a store builder with the given path added.
     *
     * @param string $path
     * @return StoreBuilder
     */
    public function addPath(string $path): StoreBuilder
    {
        return new self(array_merge($this->paths, [$path]), $this->names, $this->shortCircuit, $this->fileEncoding);
    }

    /**
     * Creates a store builder with the given name added.
     *
     * @param string $name
     * @return StoreBuilder
     */
    public function addName(string $name): StoreBuilder
    {
        return new self($this->paths, array_merge($this->names, [$name]), $this->shortCircuit, $this->fileEncoding);
    }

    /**
     * Creates a store builder with short circuit mode enabled.
     *
     * @return StoreBuilder
     */
    public function shortCircuit(): StoreBuilder
    {
        return new self($this->paths, $this->names, true, $this->fileEncoding);
    }

    /**
     * Creates a store builder with the specified file encoding.
     *
     * @param string|null $fileEncoding
     * @return StoreBuilder
     */
    public function fileEncoding(?string $fileEncoding = null): StoreBuilder
    {
        return new self($this->paths, $this->names, $this->shortCircuit, $fileEncoding);
    }

    /**
     * Creates a new store instance.
     *
     * @return StoreInterface|FileStore
     */
    public function make(): StoreInterface|FileStore
    {
        return new FileStore(
            Paths::filePaths($this->paths, $this->names),
            $this->shortCircuit,
            $this->fileEncoding
        );
    }
}
