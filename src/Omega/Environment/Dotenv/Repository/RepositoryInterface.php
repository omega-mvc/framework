<?php

declare(strict_types=1);

namespace Omega\Environment\Dotenv\Repository;

use InvalidArgumentException;

interface RepositoryInterface
{
    /**
     * Determine if the given environment variable is defined.
     *
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool;

    /**
     * Get an environment variable.
     *
     * @param string $name
     * @return string|null
     * @throws InvalidArgumentException
     */
    public function get(string $name): ?string;

    /**
     * Set an environment variable.
     *
     * @param string $name
     * @param string $value
     * @return bool
     * @throws InvalidArgumentException
     */
    public function set(string $name, string $value): bool;

    /**
     * Clear an environment variable.
     *
     * @param string $name
     * @return bool
     * @throws InvalidArgumentException
     * @return bool
     */
    public function clear(string $name): bool;
}
