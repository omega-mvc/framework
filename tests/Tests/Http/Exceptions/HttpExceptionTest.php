<?php

declare(strict_types=1);

namespace Tests\Http\Exceptions;

use Omega\Http\Exceptions\HttpException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(HttpException::class)]
final class HttpExceptionTest extends TestCase
{
    #[Test]
    public function testExposesStatusMessageCodeHeadersAndPreviousException(): void
    {
        $previous  = new RuntimeException('previous');
        $exception = new HttpException(429, 'Too Many Requests', $previous, ['X-Retry-After' => '1'], 7);

        $this->assertSame(429, $exception->getStatusCode());
        $this->assertSame('Too Many Requests', $exception->getMessage());
        $this->assertSame(7, $exception->getCode());
        $this->assertSame(['X-Retry-After' => '1'], $exception->getHeaders());
        $this->assertSame($previous, $exception->getPrevious());
    }

    #[Test]
    public function testUsesEmptyHeadersAndNoPreviousExceptionByDefault(): void
    {
        $exception = new HttpException(404, 'Not Found');

        $this->assertSame(404, $exception->getStatusCode());
        $this->assertSame([], $exception->getHeaders());
        $this->assertNull($exception->getPrevious());
        $this->assertSame(0, $exception->getCode());
    }
}
