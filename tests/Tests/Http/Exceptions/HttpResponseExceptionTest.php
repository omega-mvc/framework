<?php

declare(strict_types=1);

namespace Tests\Http\Exceptions;

use Omega\Http\Exceptions\HttpResponseException;
use Omega\Http\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(HttpResponseException::class)]
final class HttpResponseExceptionTest extends TestCase
{
    #[Test]
    public function testExposesTheAssociatedResponse(): void
    {
        $response  = new Response('direct', 202);
        $exception = new HttpResponseException($response);

        $this->assertSame($response, $exception->getResponse());
        $this->assertSame('', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertInstanceOf(RuntimeException::class, $exception);
    }
}
