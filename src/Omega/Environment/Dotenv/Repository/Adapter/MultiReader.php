<?php

declare(strict_types=1);

namespace Omega\Environment\Dotenv\Repository\Adapter;

use Omega\Environment\Dotenv\Option\AbstractOption;
use Omega\Environment\Dotenv\Option\None;

readonly class MultiReader implements ReaderInterface
{
    /**
     * Create a new multi-reader instance.
     *
     * @param ReaderInterface[] $readers
     * @return void
     */
    public function __construct(
        private array $readers
    ) {
    }

    /**
     * Read an environment variable, if it exists.
     *
     * @param non-empty-string $name
     * @return AbstractOption<string>
     */
    public function read(string $name): AbstractOption
    {
        foreach ($this->readers as $reader) {
            $result = $reader->read($name);
            if ($result->isDefined()) {
                return $result;
            }
        }

        return None::create();
    }
}
