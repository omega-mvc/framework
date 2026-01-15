<?php

declare(strict_types=1);

namespace Omega\Environment\Dotenv\Repository;

use InvalidArgumentException;
use Omega\Environment\Dotenv\Repository\Adapter\ReaderInterface;
use Omega\Environment\Dotenv\Repository\Adapter\WriterInterface;

readonly class AdapterRepository implements RepositoryInterface
{
    /**
     * Create a new adapter repository instance.
     *
     * @param ReaderInterface $reader
     * @param WriterInterface $writer
     * @return void
     */
    public function __construct(
        private ReaderInterface $reader,
        private WriterInterface $writer
    ) {
    }

    /**
     * Determine if the given environment variable is defined.
     *
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return '' !== $name && $this->reader->read($name)->isDefined();
    }

    /**
     * Get an environment variable.
     *
     * @param string $name
     * @return string|null
     * @return string|null
     * @throws InvalidArgumentException
     */
    public function get(string $name): ?string
    {
        if ('' === $name) {
            throw new InvalidArgumentException('Expected name to be a non-empty string.');
        }

        return $this->reader->read($name)->getOrElse(null);
    }

    /**
     * Set an environment variable.
     *
     * @param string $name
     * @param string $value
     * @return bool
     * @throws InvalidArgumentException
     */
    public function set(string $name, string $value): bool
    {
        if ('' === $name) {
            throw new InvalidArgumentException('Expected name to be a non-empty string.');
        }

        return $this->writer->write($name, $value);
    }

    /**
     * Clear an environment variable.
     *
     * @param string $name
     * @return bool
     * @throws InvalidArgumentException
     */
    public function clear(string $name): bool
    {
        if ('' === $name) {
            throw new InvalidArgumentException('Expected name to be a non-empty string.');
        }

        return $this->writer->delete($name);
    }
}
