<?php

declare(strict_types=1);

namespace Omega\Router\Exceptions;

use Exception;

class RouteNotRegisteredException extends Exception
{
    public function __construct(string $name)
    {
        parent::__construct(
            sprintf('Route property or method [%s] is not registered.', $name)
        );
    }
}
