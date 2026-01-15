<?php

namespace Omega\Environment\Dotenv\Option;

use ArrayIterator;
use Exception;
use RuntimeException;

class Some extends AbstractOption
{
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public static function create($value): self
    {
        return new self($value);
    }

    public function isDefined(): bool
    {
        return true;
    }

    public function isEmpty(): bool
    {
        return false;
    }

    public function get()
    {
        return $this->value;
    }

    public function getOrElse($default)
    {
        return $this->value;
    }

    public function getOrCall($callable)
    {
        return $this->value;
    }

    public function getOrThrow(Exception $ex)
    {
        return $this->value;
    }

    public function orElse(AbstractOption $else): static
    {
        return $this;
    }

    public function ifDefined($callable): void
    {
        $this->forAll($callable);
    }

    public function forAll($callable): static
    {
        $callable($this->value);

        return $this;
    }

    public function map($callable): Some
    {
        return new self($callable($this->value));
    }

    public function flatMap($callable)
    {
        /** @var mixed $rs */
        $rs = $callable($this->value);
        if (!$rs instanceof AbstractOption) {
            throw new RuntimeException(
                'Callables passed to flatMap() must return an Option. Maybe you should use map() instead?'
            );
        }

        return $rs;
    }

    public function filter($callable): None|static
    {
        if (true === $callable($this->value)) {
            return $this;
        }

        return None::create();
    }

    public function filterNot($callable): None|static
    {
        if (false === $callable($this->value)) {
            return $this;
        }

        return None::create();
    }

    public function select($value): None|static
    {
        if ($this->value === $value) {
            return $this;
        }

        return None::create();
    }

    public function reject($value): None|static
    {
        if ($this->value === $value) {
            return None::create();
        }

        return $this;
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator([$this->value]);
    }

    public function foldLeft($initialValue, $callable)
    {
        return $callable($initialValue, $this->value);
    }

    public function foldRight($initialValue, $callable)
    {
        return $callable($this->value, $initialValue);
    }
}
