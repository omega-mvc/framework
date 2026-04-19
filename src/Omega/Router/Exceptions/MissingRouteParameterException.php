<?php

declare(strict_types=1);

namespace Omega\Router\Exceptions;

use InvalidArgumentException;

class MissingRouteParameterException extends InvalidArgumentException
{
    public function __construct(
        string|int $identifier,
        ?string $context = null
    ) {
        $message = is_int($identifier)
            ? sprintf('Missing route parameter at index [%d].', $identifier)
            : sprintf('Missing route parameter [%s].', $identifier);

        if ($context !== null) {
            $message .= sprintf(' Context: %s.', $context);
        }

        parent::__construct($message);
    }

    public static function named(string $name): self
    {
        return new self($name, 'named parameter');
    }

    public static function indexed(int $index): self
    {
        return new self($index, 'indexed parameter');
    }
}
