<?php

declare(strict_types=1);

namespace Omega\Environment\Dotenv\Repository\Adapter;

class ImmutableWriter implements WriterInterface
{
    /** @var array<string, string> The record of loaded variables. */
    private array $loaded;

    /**
     * Create a new immutable writer instance.
     *
     * @param WriterInterface $writer
     * @param ReaderInterface $reader
     *
     * @return void
     */
    public function __construct(
        private readonly WriterInterface $writer,
        private readonly ReaderInterface $reader
    ) {
        $this->loaded = [];
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
        // Don't overwrite existing environment variables
        // Ruby's dotenv does this with `ENV[key] ||= value`
        if ($this->isExternallyDefined($name)) {
            return false;
        }

        // Set the value on the inner writer
        if (!$this->writer->write($name, $value)) {
            return false;
        }

        // Record that we have loaded the variable
        $this->loaded[$name] = '';

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
        // Don't clear existing environment variables
        if ($this->isExternallyDefined($name)) {
            return false;
        }

        // Clear the value on the inner writer
        if (!$this->writer->delete($name)) {
            return false;
        }

        // Leave the variable as fair game
        unset($this->loaded[$name]);

        return true;
    }

    /**
     * Determine if the given variable is externally defined.
     *
     * That is, is it an "existing" variable.
     *
     * @param non-empty-string $name
     * @return bool
     */
    private function isExternallyDefined(string $name): bool
    {
        return $this->reader->read($name)->isDefined() && !isset($this->loaded[$name]);
    }
}
