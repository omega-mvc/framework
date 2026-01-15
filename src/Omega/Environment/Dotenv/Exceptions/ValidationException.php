<?php

declare(strict_types=1);

namespace Omega\Environment\Dotenv\Exceptions;

use RuntimeException;

class ValidationException extends RuntimeException implements DotenvExceptionInterface
{
}
