<?php

declare(strict_types=1);

namespace Tests\Http\Support;

use Omega\Http\Response;
use PHPUnit\Framework\Attributes\CoversNothing;

#[CoversNothing]
final class ResponseNonCommandLine extends Response
{
    protected function isCommandLine(): bool
    {
        return false;
    }
}
