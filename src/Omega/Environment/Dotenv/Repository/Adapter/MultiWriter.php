<?php

declare(strict_types=1);

namespace Omega\Environment\Dotenv\Repository\Adapter;

use function array_all;

readonly class MultiWriter implements WriterInterface
{
    /**
     * Create a new multi-writer instance.
     *
     * @paramWriterInterface[] $writers
     * @return void
     */
    public function __construct(
        private array $writers
    ) {
    }

    /**
     * Write to an environment variable, if possible.
     *
     * @param non-empty-string $name
     * @param string           $value
     * @return bool
     */
    public function write(string $name, string $value): bool
    {
        return array_all($this->writers, fn($writers) => $writers->write($name, $value));
    }

    /**
     * Delete an environment variable, if possible.
     *
     * @param non-empty-string $name
     * @return bool
     */
    public function delete(string $name): bool
    {
        return array_all($this->writers, fn($writers) => $writers->delete($name));
    }
}
