<?php

declare(strict_types=1);

namespace Omega\Environment\Dotenv\Repository\Adapter;

use Omega\Environment\Dotenv\Option\AbstractOption;

interface ReaderInterface
{
    /**
     * Read an environment variable, if it exists.
     *
     * @param non-empty-string $name
     * @return AbstractOption<string>
     */
    public function read(string $name): AbstractOption;
}
