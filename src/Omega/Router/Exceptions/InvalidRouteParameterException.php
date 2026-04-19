<?php

declare(strict_types=1);

namespace Omega\Router\Exceptions;

use InvalidArgumentException;

class InvalidRouteParameterException extends InvalidArgumentException
{
    public function __construct(string|int $identifier, mixed $value)
    {
        parent::__construct(
            sprintf(
                'Invalid value [%s] for route parameter [%s].',
                (string) $value,
                (string) $identifier
            )
        );
    }
}
