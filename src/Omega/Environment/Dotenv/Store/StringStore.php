<?php

declare(strict_types=1);

namespace Omega\Environment\Dotenv\Store;

readonly class StringStore implements StoreInterface
{
    /**
     * Create a new string store instance.
     *
     * @param string $content
     * @return void
     */
    public function __construct(
        private string $content
    ) {
    }

    /**
     * Read the content of the environment file(s).
     *
     * @return string
     */
    public function read(): string
    {
        return $this->content;
    }
}
