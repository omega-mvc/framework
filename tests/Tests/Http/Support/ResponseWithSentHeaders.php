<?php

declare(strict_types=1);

namespace Tests\Http\Support;

use Omega\Http\Response;
use PHPUnit\Framework\Attributes\CoversNothing;

#[CoversNothing]
final class ResponseWithSentHeaders extends Response
{
    protected function headersSent(): bool
    {
        return true;
    }
}
