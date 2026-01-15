<?php

declare(strict_types=1);

namespace Omega\Environment\Dotenv\Parser;

use Omega\Environment\Dotenv\Util\Str;

use function array_merge;
use function rsort;

readonly class Value
{
    /**
     * Internal constructor for a value.
     *
     * @param string $chars
     * @param int[]  $vars
     *
     * @return void
     */
    private function __construct(
        private string $chars,
        private array $vars
    ) {
    }

    /**
     * Create an empty value instance.
     *
     * @return Value
     */
    public static function blank(): Value
    {
        return new self('', []);
    }

    /**
     * Create a new value instance, appending the characters.
     *
     * @param string $chars
     * @param bool   $var
     * @return Value
     */
    public function append(string $chars, bool $var): Value
    {
        return new self(
            $this->chars . $chars,
            $var ? array_merge($this->vars, [Str::len($this->chars)]) : $this->vars
        );
    }

    /**
     * Get the string representation of the parsed value.
     *
     * @return string
     */
    public function getChars(): string
    {
        return $this->chars;
    }

    /**
     * Get the locations of the variables in the value.
     *
     * @return int[]
     */
    public function getVars(): array
    {
        $vars = $this->vars;

        rsort($vars);

        return $vars;
    }
}
