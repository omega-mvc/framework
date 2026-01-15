<?php

declare(strict_types=1);

namespace Omega\Environment\Dotenv\Repository\Adapter;

use function in_array;

readonly class GuardedWriter implements WriterInterface
{
    /**
     * Create a new guarded writer instance.
     *
     * @param WriterInterface $writer
     * @param string[]        $allowList
     *
     * @return void
     */
    public function __construct(
        private WriterInterface $writer,
        private array $allowList
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
        // Don't set non-allowed variables
        if (!$this->isAllowed($name)) {
            return false;
        }

        // Set the value on the inner writer
        return $this->writer->write($name, $value);
    }

    /**
     * Delete an environment variable, if possible.
     *
     * @param non-empty-string $name
     * @return bool
     */
    public function delete(string $name): bool
    {
        // Don't clear non-allowed variables
        if (!$this->isAllowed($name)) {
            return false;
        }

        // Set the value on the inner writer
        return $this->writer->delete($name);
    }

    /**
     * Determine if the given variable is allowed.
     *
     * @param non-empty-string $name
     * @return bool
     */
    private function isAllowed(string $name): bool
    {
        return in_array($name, $this->allowList, true);
    }
}
