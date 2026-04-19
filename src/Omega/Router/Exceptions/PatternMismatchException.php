<?php

declare(strict_types=1);

namespace Omega\Router\Exceptions;

use InvalidArgumentException;

class PatternMismatchException extends InvalidArgumentException
{
    public function __construct(
        string|int $identifier,
        mixed $value,
        string $pattern,
        string $regex
    ) {
        parent::__construct(
            sprintf(
                'Parameter [%s] with value [%s] does not match pattern %s (%s).',
                (string) $identifier,
                (string) $value,
                $pattern,
                $regex
            )
        );
    }
}
