<?php

declare(strict_types=1);

namespace Omega\Environment\Dotenv\Exceptions;

use InvalidArgumentException;

class InvalidEncodingException extends InvalidArgumentException implements DotenvExceptionInterface
{
}
