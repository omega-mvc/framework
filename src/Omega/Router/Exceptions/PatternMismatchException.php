<?php

declare(strict_types=1);

namespace Omega\Router\Exceptions;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_object;
use function is_string;
use function json_encode;

class PatternMismatchException extends InvalidArgumentException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function forNamed(string $name, mixed $value, string $pattern, string $regex): self
    {
        return new self(sprintf(
            "Named parameter '%s' with value '%s' doesn't match pattern %s (%s)",
            $name,
            self::valueToString($value),
            $pattern,
            $regex
        ));
    }

    public static function forValue(mixed $value, string $pattern, string $regex): self
    {
        return new self(sprintf(
            "Parameter '%s' doesn't match pattern %s (%s)",
            self::valueToString($value),
            $pattern,
            $regex
        ));
    }

    private static function valueToString(mixed $value): string
    {
        if (is_string($value) || is_int($value) || is_float($value) || is_bool($value)) {
            return (string) $value;
        }

        if ($value instanceof Stringable) {
            return (string) $value;
        }

        if ($value instanceof JsonSerializable || is_array($value) || is_object($value)) {
            $encoded = json_encode($value);
            if (false !== $encoded) {
                return $encoded;
            }
        }

        return 'null';
    }
}
