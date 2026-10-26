<?php

declare(strict_types=1);

namespace Tests\Csrf\Exceptions;

use Omega\Csrf\Exceptions\CsrfTokenExceptionInterface;
use Omega\Csrf\Exceptions\InvalidCsrfTokenException;
use Omega\Csrf\Exceptions\MalformedCsrfTokenException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(InvalidCsrfTokenException::class)]
#[CoversClass(MalformedCsrfTokenException::class)]
final class CsrfExceptionTest extends TestCase
{
    public function testInvalidTokenExceptionUsesItsDefaultMessage(): void
    {
        $exception = new InvalidCsrfTokenException();

        $this->assertInstanceOf(CsrfTokenExceptionInterface::class, $exception);
        $this->assertSame('Invalid CSRF token.', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testInvalidTokenExceptionForwardsTheGivenArguments(): void
    {
        $previous  = new RuntimeException('previous');
        $exception = new InvalidCsrfTokenException('custom', 42, $previous);

        $this->assertSame('custom', $exception->getMessage());
        $this->assertSame(42, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testMalformedTokenExceptionUsesItsDefaultMessage(): void
    {
        $exception = new MalformedCsrfTokenException();

        $this->assertInstanceOf(CsrfTokenExceptionInterface::class, $exception);
        $this->assertSame('Malformed CSRF token.', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testMalformedTokenExceptionForwardsTheGivenArguments(): void
    {
        $previous  = new RuntimeException('previous');
        $exception = new MalformedCsrfTokenException('custom', 42, $previous);

        $this->assertSame('custom', $exception->getMessage());
        $this->assertSame(42, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
