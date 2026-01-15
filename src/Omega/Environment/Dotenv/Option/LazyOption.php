<?php

namespace Omega\Environment\Dotenv\Option;

use Exception;
use Omega\Environment\Dotenv\Exceptions\InvalidCallbackException;
use Omega\Environment\Dotenv\Exceptions\InvalidInstanceException;
use Traversable;

use function call_user_func_array;
use function is_callable;
use function sprintf;

class LazyOption extends AbstractOption
{
    private $callback;

    private array $arguments;

    private $option;

    public static function create($callback, array $arguments = []): self
    {
        return new self($callback, $arguments);
    }

    public function __construct($callback, array $arguments = [])
    {
        if (!is_callable($callback)) {
            throw new InvalidCallbackException(
                'Invalid callback given'
            );
        }

        $this->callback = $callback;
        $this->arguments = $arguments;
    }

    public function isDefined(): bool
    {
        return $this->option()->isDefined();
    }

    public function isEmpty(): bool
    {
        return $this->option()->isEmpty();
    }

    public function get()
    {
        return $this->option()->get();
    }

    public function getOrElse($default)
    {
        return $this->option()->getOrElse($default);
    }

    public function getOrCall($callable): callable
    {
        return $this->option()->getOrCall($callable);
    }

    public function getOrThrow(Exception $ex)
    {
        return $this->option()->getOrThrow($ex);
    }

    public function orElse(AbstractOption $else): AbstractOption
    {
        return $this->option()->orElse($else);
    }

    public function ifDefined($callable): void
    {
        $this->option()->forAll($callable);
    }

    public function forAll($callable): AbstractOption
    {
        return $this->option()->forAll($callable);
    }

    public function map($callable): AbstractOption
    {
        return $this->option()->map($callable);
    }

    public function flatMap($callable): AbstractOption
    {
        return $this->option()->flatMap($callable);
    }

    public function filter($callable): AbstractOption
    {
        return $this->option()->filter($callable);
    }

    public function filterNot($callable): AbstractOption
    {
        return $this->option()->filterNot($callable);
    }

    public function select($value): AbstractOption
    {
        return $this->option()->select($value);
    }

    public function reject($value): AbstractOption
    {
        return $this->option()->reject($value);
    }

    public function getIterator(): Traversable
    {
        return $this->option()->getIterator();
    }

    public function foldLeft($initialValue, $callable)
    {
        return $this->option()->foldLeft($initialValue, $callable);
    }

    public function foldRight($initialValue, $callable)
    {
        return $this->option()->foldRight($initialValue, $callable);
    }

    private function option(): AbstractOption
    {
        if (null === $this->option) {
            $option = call_user_func_array($this->callback, $this->arguments);
            if ($option instanceof AbstractOption) {
                $this->option = $option;
            } else {
                throw new InvalidInstanceException(
                    sprintf(
                        'Expected instance of %s',
                        AbstractOption::class
                    )
                );
            }
        }

        return $this->option;
    }
}
