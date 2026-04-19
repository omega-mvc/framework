<?php

declare(strict_types=1);

namespace Omega\Router\Exceptions;

use InvalidArgumentException;

class UnknownRoutePatternException extends InvalidArgumentException
{
    public function __construct(string $pattern)
    {
        parent::__construct(
            sprintf('Unknown route pattern [%s].', $pattern)
        );
    }
}
