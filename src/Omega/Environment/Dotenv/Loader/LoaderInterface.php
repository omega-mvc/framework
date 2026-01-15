<?php

declare(strict_types=1);

namespace Omega\Environment\Dotenv\Loader;

use Omega\Environment\Dotenv\Parser\Entry;
use Omega\Environment\Dotenv\Repository\RepositoryInterface;

interface LoaderInterface
{
    /**
     * Load the given entries into the repository.
     *
     * @param RepositoryInterface $repository
     * @param Entry[]             $entries
     * @return array<string, string|null>
     */
    public function load(RepositoryInterface $repository, array $entries): array;
}
