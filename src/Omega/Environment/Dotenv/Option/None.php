<?php

namespace Omega\Environment\Dotenv\Option;

use EmptyIterator;
use Exception;
use Omega\Environment\Dotenv\Exceptions\InvalidValueException;

class None extends AbstractOption
{
    /** @var None|null */
    private static ?None $instance = null;

    /**
     * @return None
     */
    public static function create(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function get()
    {
        throw new InvalidValueException(
            'None has no value.'
        );
    }

    public function getOrCall($callable)
    {
        return $callable();
    }

    public function getOrElse($default)
    {
        return $default;
    }

    /**
     * @throws Exception
     */
    public function getOrThrow(Exception $ex)
    {
        throw $ex;
    }

    public function isEmpty(): bool
    {
        return true;
    }

    public function isDefined(): bool
    {
        return false;
    }

    public function orElse(AbstractOption $else): AbstractOption
    {
        return $else;
    }

    public function ifDefined($callable)
    {
    }

    public function forAll($callable): AbstractOption|static
    {
        return $this;
    }

    public function map($callable): AbstractOption|static
    {
        return $this;
    }

    public function flatMap($callable): AbstractOption|static
    {
        return $this;
    }

    public function filter($callable): AbstractOption|static
    {
        return $this;
    }

    public function filterNot($callable): AbstractOption|static
    {
        return $this;
    }

    public function select($value): AbstractOption|static
    {
        return $this;
    }

    public function reject($value): AbstractOption|static
    {
        return $this;
    }

    public function getIterator(): EmptyIterator
    {
        return new EmptyIterator();
    }

    public function foldLeft($initialValue, $callable)
    {
        return $initialValue;
    }

    public function foldRight($initialValue, $callable)
    {
        return $initialValue;
    }

    private function __construct()
    {
    }
}
