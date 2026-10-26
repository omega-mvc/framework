<?php

declare(strict_types=1);

namespace Tests\Csrf\Facade;

use Omega\Csrf\Facade\Csrf;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(Csrf::class)]
final class CsrfTest extends TestCase
{
    public function testReturnsTheFacadeAccessor(): void
    {
        $this->assertSame('csrf', Csrf::getFacadeAccessor());
    }
}
