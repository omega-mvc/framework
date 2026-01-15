<?php

declare(strict_types=1);

namespace Omega\Environment\Dotenv\Parser;

use Omega\Environment\Dotenv\Option\AbstractOption;

readonly class Entry
{
    /**
     * Create a new entry instance.
     *
     * @param string     $name
     * @param Value|null $value
     * @return void
     */
    public function __construct(
        private string $name,
        private ?Value $value = null
    ) {
    }

    /**
     * Get the entry name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the entry value.
     *
     * @return AbstractOption
     */
    public function getValue(): AbstractOption
    {
        return AbstractOption::fromValue($this->value);
    }
}
