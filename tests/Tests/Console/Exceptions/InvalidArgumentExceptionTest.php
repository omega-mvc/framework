<?php

declare(strict_types=1);

namespace Tests\Console\Exceptions;

use LogicException;
use Omega\Console\Exceptions\InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

use function get_parent_class;

#[CoversClass(InvalidArgumentException::class)]
final class InvalidArgumentExceptionTest extends TestCase
{
    public function testIsALogicException(): void
    {
        $exception = new InvalidArgumentException('Invalid configuration.');

        $this->assertSame(LogicException::class, get_parent_class($exception));
    }

    public function testCarriesTheConfigurationMessage(): void
    {
        $exception = new InvalidArgumentException('Invalid configuration.');

        $this->assertSame('Invalid configuration.', $exception->getMessage());
    }
}
