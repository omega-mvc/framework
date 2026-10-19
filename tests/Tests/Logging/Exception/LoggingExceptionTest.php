<?php

declare(strict_types=1);

namespace Tests\Logging\Exception;

use InvalidArgumentException;
use Omega\Logging\Exception\LogArgumentException;
use Omega\Logging\Exception\UnknownDriverException;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionClass;
use Tests\TestCase;

#[CoversClass(LogArgumentException::class)]
#[CoversClass(UnknownDriverException::class)]
final class LoggingExceptionTest extends TestCase
{
    public function testConstructsALogArgumentException(): void
    {
        $exception = new LogArgumentException('invalid log');
        $parent    = (new ReflectionClass($exception))->getParentClass();

        $this->assertNotFalse($parent);
        $this->assertSame(InvalidArgumentException::class, $parent->getName());
        $this->assertSame('invalid log', $exception->getMessage());
    }

    public function testConstructsAnUnknownDriverException(): void
    {
        $exception = new UnknownDriverException('stream');

        $this->assertSame(
            'The log driver "stream" could not be resolved or is not registered.',
            $exception->getMessage()
        );
    }
}
