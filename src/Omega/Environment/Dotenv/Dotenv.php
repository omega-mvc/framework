<?php

declare(strict_types=1);

namespace Omega\Environment\Dotenv;

use Omega\Environment\Dotenv\Exceptions\InvalidEncodingException;
use Omega\Environment\Dotenv\Exceptions\InvalidFileException;
use Omega\Environment\Dotenv\Exceptions\InvalidPathException;
use Omega\Environment\Dotenv\Loader\Loader;
use Omega\Environment\Dotenv\Loader\LoaderInterface;
use Omega\Environment\Dotenv\Parser\Parser;
use Omega\Environment\Dotenv\Parser\ParserInterface;
use Omega\Environment\Dotenv\Repository\Adapter\ArrayAdapter;
use Omega\Environment\Dotenv\Repository\Adapter\PutenvAdapter;
use Omega\Environment\Dotenv\Repository\RepositoryBuilder;
use Omega\Environment\Dotenv\Repository\RepositoryInterface;
use Omega\Environment\Dotenv\Store\StoreBuilder;
use Omega\Environment\Dotenv\Store\StoreInterface;
use Omega\Environment\Dotenv\Store\StringStore;

readonly class Dotenv
{
    /**
     * Create a new dotenv instance.
     *
     * @param StoreInterface      $store
     * @param ParserInterface     $parser
     * @param LoaderInterface     $loader
     * @param RepositoryInterface $repository
     * @return void
     */
    public function __construct(
        private StoreInterface $store,
        private ParserInterface $parser,
        private LoaderInterface $loader,
        private RepositoryInterface $repository
    ) {
    }

    /**
     * Create a new dotenv instance.
     *
     * @param RepositoryInterface  $repository
     * @param string|string[]      $paths
     * @param string|string[]|null $names
     * @param bool                 $shortCircuit
     * @param string|null          $fileEncoding
     * @return Dotenv
     */
    public static function create(
        RepositoryInterface $repository,
        string|array $paths,
        string|array|null $names = null,
        bool $shortCircuit = true,
        ?string $fileEncoding = null
    ): Dotenv {
        $builder = $names === null ? StoreBuilder::createWithDefaultName() : StoreBuilder::createWithNoNames();

        foreach ((array) $paths as $path) {
            $builder = $builder->addPath($path);
        }

        foreach ((array) $names as $name) {
            $builder = $builder->addName($name);
        }

        if ($shortCircuit) {
            $builder = $builder->shortCircuit();
        }

        return new self($builder->fileEncoding($fileEncoding)->make(), new Parser(), new Loader(), $repository);
    }

    /**
     * Create a new mutable dotenv instance with default repository.
     *
     * @param string|string[]      $paths
     * @param string|string[]|null $names
     * @param bool                 $shortCircuit
     * @param string|null          $fileEncoding
     * @return Dotenv
     */
    public static function createMutable(
        string|array $paths,
        string|array|null $names = null,
        bool $shortCircuit = true,
        ?string $fileEncoding = null
    ): Dotenv {
        $repository = RepositoryBuilder::createWithDefaultAdapters()->make();

        return self::create($repository, $paths, $names, $shortCircuit, $fileEncoding);
    }

    /**
     * Create a new mutable dotenv instance with default repository with the putenv adapter.
     *
     * @param string|string[]      $paths
     * @param string|string[]|null $names
     * @param bool                 $shortCircuit
     * @param string|null          $fileEncoding
     * @return Dotenv
     */
    public static function createUnsafeMutable(
        string|array $paths,
        string|array|null $names = null,
        bool $shortCircuit = true,
        ?string $fileEncoding = null
    ): Dotenv {
        $repository = RepositoryBuilder::createWithDefaultAdapters()
            ->addAdapter(PutenvAdapter::class)
            ->make();

        return self::create($repository, $paths, $names, $shortCircuit, $fileEncoding);
    }

    /**
     * Create a new immutable dotenv instance with default repository.
     *
     * @param string|string[]      $paths
     * @param string|string[]|null $names
     * @param bool                 $shortCircuit
     * @param string|null          $fileEncoding
     * @return Dotenv
     */
    public static function createImmutable(
        string|array $paths,
        string|array|null $names = null,
        bool $shortCircuit = true,
        ?string $fileEncoding = null
    ): Dotenv {
        $repository = RepositoryBuilder::createWithDefaultAdapters()->immutable()->make();

        return self::create($repository, $paths, $names, $shortCircuit, $fileEncoding);
    }

    /**
     * Create a new immutable dotenv instance with default repository with the putenv adapter.
     *
     * @param string|string[]      $paths
     * @param string|string[]|null $names
     * @param bool                 $shortCircuit
     * @param string|null          $fileEncoding
     * @return Dotenv
     */
    public static function createUnsafeImmutable(
        string|array $paths,
        string|array|null $names = null,
        bool $shortCircuit = true,
        ?string $fileEncoding = null
    ): Dotenv {
        $repository = RepositoryBuilder::createWithDefaultAdapters()
            ->addAdapter(PutenvAdapter::class)
            ->immutable()
            ->make();

        return self::create($repository, $paths, $names, $shortCircuit, $fileEncoding);
    }

    /**
     * Create a new dotenv instance with an array backed repository.
     *
     * @param string|string[]      $paths
     * @param string|string[]|null $names
     * @param bool                 $shortCircuit
     * @param string|null          $fileEncoding
     * @return Dotenv
     */
    public static function createArrayBacked(
        string|array $paths,
        string|array|null $names = null,
        bool $shortCircuit = true,
        ?string $fileEncoding = null
    ): Dotenv {
        $repository = RepositoryBuilder::createWithNoAdapters()->addAdapter(ArrayAdapter::class)->make();

        return self::create($repository, $paths, $names, $shortCircuit, $fileEncoding);
    }

    /**
     * Parse the given content and resolve nested variables.
     *
     * This method behaves just like load(), only without mutating your actual
     * environment. We do this by using an array backed repository.
     *
     * @param string $content
     * @return array<string, string|null>
     * @throws InvalidFileException
     */
    public static function parse(string $content): array
    {
        $repository = RepositoryBuilder::createWithNoAdapters()->addAdapter(ArrayAdapter::class)->make();

        $env        = new self(new StringStore($content), new Parser(), new Loader(), $repository);

        return $env->load();
    }

    /**
     * Read and load environment file(s).
     *
     * @return array<string, string|null>
     * @throws InvalidPathException
     * @throws InvalidEncodingException
     * @throws InvalidFileException
     *
     */
    public function load(): array
    {
        $entries = $this->parser->parse($this->store->read());

        return $this->loader->load($this->repository, $entries);
    }

    /**
     * Read and load environment file(s), silently failing if no files can be read.
     *
     * @return array<string, string|null>
     * @throws InvalidEncodingException|InvalidFileException
     */
    public function safeLoad(): array
    {
        try {
            return $this->load();
        } catch (InvalidPathException $e) {
            // suppressing exception
            return [];
        }
    }

    /**
     * Required ensures that the specified variables exist, and returns a new validator object.
     *
     * @param string|string[] $variables
     * @return Validator
     */
    public function required(array|string $variables): Validator
    {
        return new Validator($this->repository, (array) $variables)->required();
    }

    /**
     * Returns a new validator object that won't check if the specified variables exist.
     *
     * @param string|string[] $variables
     * @return Validator
     */
    public function ifPresent(array|string $variables): Validator
    {
        return new Validator($this->repository, (array) $variables);
    }
}
