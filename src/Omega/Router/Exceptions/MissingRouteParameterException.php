<?php

declare(strict_types=1);

namespace Omega\Router\Exceptions;

use InvalidArgumentException;

class MissingRouteParameterException extends InvalidArgumentException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function named(string $name): self
    {
        return new self(sprintf('Missing named parameter: %s', $name));
    }

    public static function namedIndexed(int $index, string $name): self
    {
        return new self(sprintf('Missing parameter at index %d for named parameter %s', $index, $name));
    }

    public static function patternAssoc(string $pattern, int $index): self
    {
        $type = trim($pattern, '(:)');

        return new self(sprintf(
            "Missing parameter for pattern {%s}. Provide either numeric index {%d} or key '{%s}'",
            $pattern,
            $index,
            $type
        ));
    }

    public static function patternIndexed(int $index, string $pattern): self
    {
        return new self(sprintf('Missing parameter at index %d for pattern %s', $index, $pattern));
    }
}
