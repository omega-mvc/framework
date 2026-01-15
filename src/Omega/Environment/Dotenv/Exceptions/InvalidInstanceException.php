<?php

declare(strict_types=1);

namespace Omega\Environment\Dotenv\Exceptions;

use RuntimeException;

class InvalidInstanceException extends RuntimeException implements DotenvExceptionInterface
{
}
