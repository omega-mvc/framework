<?php

declare(strict_types=1);

namespace Omega\Environment\Dotenv\Repository\Adapter;

class ReplacingWriter implements WriterInterface
{
    /** @var array<string, string> The record of seen variables. */
    private array $seen;

    /**
     * Create a new replacement writer instance.
     *
     * @param WriterInterface $writer
     * @param ReaderInterface $reader
     * @return void
     */
    public function __construct(
        private readonly WriterInterface $writer,
        private readonly ReaderInterface $reader
    ) {
        $this->seen = [];
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
        if ($this->exists($name)) {
            return $this->writer->write($name, $value);
        }

        // succeed if nothing to do
        return true;
    }

    /**
     * Delete an environment variable, if possible.
     *
     * @param non-empty-string $name
     * @return bool
     */
    public function delete(string $name): bool
    {
        if ($this->exists($name)) {
            return $this->writer->delete($name);
        }

        // succeed if nothing to do
        return true;
    }

    /**
     * Does the given environment variable exist.
     *
     * Returns true if it currently exists, or existed at any point in the past
     * that we are aware of.
     *
     * @param non-empty-string $name
     * @return bool
     */
    private function exists(string $name): bool
    {
        if (isset($this->seen[$name])) {
            return true;
        }

        if ($this->reader->read($name)->isDefined()) {
            $this->seen[$name] = '';

            return true;
        }

        return false;
    }
}
