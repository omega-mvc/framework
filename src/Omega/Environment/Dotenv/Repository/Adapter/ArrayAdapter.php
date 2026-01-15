<?php

declare(strict_types=1);

namespace Omega\Environment\Dotenv\Repository\Adapter;

use Omega\Environment\Dotenv\Option\AbstractOption;
use Omega\Environment\Dotenv\Option\Some;

class ArrayAdapter implements AdapterInterface
{
    /** @var array<string, string> The variables and their values. */
    private array $variables;

    /**
     * Create a new array adapter instance.
     *
     * @return void
     */
    private function __construct()
    {
        $this->variables = [];
    }

    /**
     * Create a new instance of the adapter, if it is available.
     *
     * @return AbstractOption<AdapterInterface>
     */
    public static function create(): AbstractOption
    {
        /** @var AbstractOption<AdapterInterface> */
        return Some::create(new self());
    }

    /**
     * Read an environment variable, if it exists.
     *
     * @param non-empty-string $name
     *
     * @return AbstractOption<string>
     */
    public function read(string $name): AbstractOption
    {
        return AbstractOption::fromArraysValue($this->variables, $name);
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
        $this->variables[$name] = $value;

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
        unset($this->variables[$name]);

        return true;
    }
}
