<?php

declare(strict_types=1);

namespace Omega\Environment\Dotenv\Repository;

use Generator;
use InvalidArgumentException;
use Omega\Environment\Dotenv\Option\Some;
use Omega\Environment\Dotenv\Repository\Adapter\AdapterInterface;
use Omega\Environment\Dotenv\Repository\Adapter\EnvConstAdapter;
use Omega\Environment\Dotenv\Repository\Adapter\GuardedWriter;
use Omega\Environment\Dotenv\Repository\Adapter\ImmutableWriter;
use Omega\Environment\Dotenv\Repository\Adapter\MultiReader;
use Omega\Environment\Dotenv\Repository\Adapter\MultiWriter;
use Omega\Environment\Dotenv\Repository\Adapter\ReaderInterface;
use Omega\Environment\Dotenv\Repository\Adapter\ServerConstAdapter;
use Omega\Environment\Dotenv\Repository\Adapter\WriterInterface;
use ReflectionClass;

use function array_merge;
use function class_exists;
use function is_string;
use function iterator_to_array;
use function sprintf;

readonly class RepositoryBuilder
{
    /**
     * The set of default adapters.
     */
    private const array DEFAULT_ADAPTERS = [
        ServerConstAdapter::class,
        EnvConstAdapter::class,
    ];

    /**
     * Create a new repository builder instance.
     *
     * @param Adapter\ReaderInterface[] $readers
     * @param WriterInterface[]         $writers
     * @param bool                      $immutable
     * @param string[]|null             $allowList
     *
     * @return void
     */
    private function __construct(
        private array $readers = [],
        private array $writers = [],
        private bool $immutable = false,
        private ?array $allowList = null
    ) {
    }

    /**
     * Create a new repository builder instance with no adapters added.
     *
     * @return RepositoryBuilder
     */
    public static function createWithNoAdapters(): RepositoryBuilder
    {
        return new self();
    }

    /**
     * Create a new repository builder instance with the default adapters added.
     *
     * @return RepositoryBuilder
     */
    public static function createWithDefaultAdapters(): RepositoryBuilder
    {
        $adapters = iterator_to_array(self::defaultAdapters());

        return new self($adapters, $adapters);
    }

    /**
     * Return the array of default adapters.
     *
     * @return Generator<AdapterInterface>
     */
    private static function defaultAdapters(): Generator
    {
        foreach (self::DEFAULT_ADAPTERS as $adapter) {
            $instance = $adapter::create();
            if ($instance->isDefined()) {
                yield $instance->get();
            }
        }
    }

    /**
     * Determine if the given name if of an adapter class.
     *
     * @param string $name
     * @return bool
     */
    private static function isAnAdapterClass(string $name): bool
    {
        if (!class_exists($name)) {
            return false;
        }

        return new ReflectionClass($name)->implementsInterface(AdapterInterface::class);
    }

    /**
     * Creates a repository builder with the given reader added.
     *
     * Accepts either a reader instance, or a class-string for an adapter. If
     * the adapter is not supported, then we silently skip adding it.
     *
     * @param string|ReaderInterface $reader
     * @return RepositoryBuilder
     * @throws InvalidArgumentException
     */
    public function addReader(ReaderInterface|string $reader): RepositoryBuilder
    {
        if (!(is_string($reader) && self::isAnAdapterClass($reader)) && !($reader instanceof ReaderInterface)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Expected either an instance of %s or a class-string implementing %s',
                    ReaderInterface::class,
                    AdapterInterface::class
                )
            );
        }

        $optional = Some::create($reader)->flatMap(static function ($reader) {
            return is_string($reader) ? $reader::create() : Some::create($reader);
        });

        $readers = array_merge($this->readers, iterator_to_array($optional));

        return new self($readers, $this->writers, $this->immutable, $this->allowList);
    }

    /**
     * Creates a repository builder with the given writer added.
     *
     * Accepts either a writer instance, or a class-string for an adapter. If
     * the adapter is not supported, then we silently skip adding it.
     *
     * @param string|WriterInterface $writer
     * @return RepositoryBuilder
     * @throws InvalidArgumentException
     */
    public function addWriter(string|WriterInterface $writer): RepositoryBuilder
    {
        if (!(is_string($writer) && self::isAnAdapterClass($writer)) && !($writer instanceof WriterInterface)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Expected either an instance of %s or a class-string implementing %s',
                    WriterInterface::class,
                    AdapterInterface::class
                )
            );
        }

        $optional = Some::create($writer)->flatMap(static function ($writer) {
            return is_string($writer) ? $writer::create() : Some::create($writer);
        });

        $writers = array_merge($this->writers, iterator_to_array($optional));

        return new self($this->readers, $writers, $this->immutable, $this->allowList);
    }

    /**
     * Creates a repository builder with the given adapter added.
     *
     * Accepts either an adapter instance, or a class-string for an adapter. If
     * the adapter is not supported, then we silently skip adding it. We will
     * add the adapter as both a reader and a writer.
     *
     * @param string|WriterInterface $adapter
     * @return RepositoryBuilder
     * @throws InvalidArgumentException
     *
     */
    public function addAdapter(string|WriterInterface $adapter): RepositoryBuilder
    {
        if (!(is_string($adapter) && self::isAnAdapterClass($adapter)) && !($adapter instanceof AdapterInterface)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Expected either an instance of %s or a class-string implementing %s',
                    WriterInterface::class,
                    AdapterInterface::class
                )
            );
        }

        $optional = Some::create($adapter)->flatMap(static function ($adapter) {
            return is_string($adapter) ? $adapter::create() : Some::create($adapter);
        });

        $readers = array_merge($this->readers, iterator_to_array($optional));
        $writers = array_merge($this->writers, iterator_to_array($optional));

        return new self($readers, $writers, $this->immutable, $this->allowList);
    }

    /**
     * Creates a repository builder with mutability enabled.
     *
     * @return RepositoryBuilder
     */
    public function immutable(): RepositoryBuilder
    {
        return new self($this->readers, $this->writers, true, $this->allowList);
    }

    /**
     * Creates a repository builder with the given allow list.
     *
     * @param string[]|null $allowList
     * @return RepositoryBuilder
     */
    public function allowList(?array $allowList = null): RepositoryBuilder
    {
        return new self($this->readers, $this->writers, $this->immutable, $allowList);
    }

    /**
     * Creates a new repository instance.
     *
     * @return RepositoryInterface
     */
    public function make(): RepositoryInterface
    {
        $reader = new MultiReader($this->readers);
        $writer = new MultiWriter($this->writers);

        if ($this->immutable) {
            $writer = new ImmutableWriter($writer, $reader);
        }

        if ($this->allowList !== null) {
            $writer = new GuardedWriter($writer, $this->allowList);
        }

        return new AdapterRepository($reader, $writer);
    }
}
