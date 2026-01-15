<?php

declare(strict_types=1);

namespace Omega\Environment\Dotenv\Store;

use Omega\Environment\Dotenv\Exceptions\InvalidEncodingException;
use Omega\Environment\Dotenv\Exceptions\InvalidPathException;

interface StoreInterface
{
    /**
     * Read the content of the environment file(s).
     *
     * @return string
     * @throws InvalidEncodingException
     * @throws InvalidPathException
     *
     */
    public function read(): string;
}
