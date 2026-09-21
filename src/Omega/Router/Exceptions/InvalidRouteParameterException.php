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

class InvalidRouteParameterException extends InvalidArgumentException
{
    public function __construct(string|int $identifier, mixed $value)
    {
        parent::__construct(
            sprintf(
                'Invalid value [%s] for route parameter [%s].',
                self::valueToString($value),
                (string) $identifier
            )
        );
    }

    private static function valueToString(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            return (string) $value;
        }

        if (is_bool($value)) {
            return (string) $value;
        }

        if ($value instanceof Stringable) {
            return (string) $value;
        }

        if ($value instanceof JsonSerializable) {
            return self::encode($value);
        }

        if (is_array($value)) {
            return self::encode($value);
        }

        if (is_object($value)) {
            return self::encode($value);
        }

        return 'null';
    }

    private static function encode(mixed $value): string
    {
        $encoded = json_encode($value);

        return false === $encoded ? 'null' : $encoded;
    }
}
