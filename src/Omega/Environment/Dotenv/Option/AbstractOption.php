<?php

namespace Omega\Environment\Dotenv\Option;

use ArrayAccess;
use Closure;
use Exception;
use IteratorAggregate;

use function array_reduce;
use function call_user_func_array;
use function func_get_args;
use function is_array;
use function is_callable;

abstract class AbstractOption implements IteratorAggregate
{
    public static function fromValue($value, $noneValue = null): None|Some
    {
        if ($value === $noneValue) {
            return None::create();
        }

        return new Some($value);
    }

    public static function fromArraysValue($array, $key): None|Some
    {
        if ($key === null || !(is_array($array) || $array instanceof ArrayAccess) || !isset($array[$key])) {
            return None::create();
        }

        return new Some($array[$key]);
    }

    public static function fromReturn($callback, array $arguments = [], $noneValue = null): LazyOption
    {
        return new LazyOption(static function () use ($callback, $arguments, $noneValue) {
            $return = call_user_func_array($callback, $arguments);

            if ($return === $noneValue) {
                return None::create();
            }

            return new Some($return);
        });
    }

    public static function ensure($value, $noneValue = null): LazyOption|AbstractOption|None|Some
    {
        if ($value instanceof self) {
            return $value;
        } elseif (is_callable($value)) {
            return new LazyOption(static function () use ($value, $noneValue) {
                /** @var mixed $return */
                $return = $value();

                if ($return instanceof self) {
                    return $return;
                } else {
                    return self::fromValue($return, $noneValue);
                }
            });
        } else {
            return self::fromValue($value, $noneValue);
        }
    }

    public static function lift($callback, $noneValue = null): Closure
    {
        return static function () use ($callback, $noneValue) {
            /** @var array<int, mixed> $args */
            $args = func_get_args();

            $reducedArgs = array_reduce(
                $args,
                /**
                 * @param bool $status
                 * @param AbstractOption $o
                 * @return bool
                 */
                static function (bool $status, self $o) {
                    return $o->isEmpty() ? true : $status;
                },
                false
            );
            if ($reducedArgs) {
                return None::create();
            }

            $args = array_map(
                static function (self $o) {
                    return $o->get();
                },
                $args
            );

            return self::ensure(call_user_func_array($callback, $args), $noneValue);
        };
    }

    abstract public function get();

    abstract public function getOrElse($default);

    abstract public function getOrCall($callable);

    abstract public function getOrThrow(Exception $ex);

    abstract public function isEmpty();

    abstract public function isDefined();

    abstract public function orElse(self $else);

    abstract public function ifDefined($callable);

    abstract public function forAll($callable);

    abstract public function map($callable);

    abstract public function flatMap($callable);

    abstract public function filter($callable);

    abstract public function filterNot($callable);

    abstract public function select($value);

    abstract public function reject($value);

    abstract public function foldLeft($initialValue, $callable);

    abstract public function foldRight($initialValue, $callable);
}
