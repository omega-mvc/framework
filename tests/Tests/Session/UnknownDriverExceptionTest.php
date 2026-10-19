<?php

declare(strict_types=1);

namespace Tests\Session;

use Omega\Session\Exceptions\UnknownDriverException;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use Tests\TestCase;

use function class_parents;

#[CoversClass(UnknownDriverException::class)]
final class UnknownDriverExceptionTest extends TestCase
{
    public function testConstructsAnUnknownDriverException(): void
    {
        $exception = new UnknownDriverException('redis');

        $this->assertContains(RuntimeException::class, class_parents(UnknownDriverException::class));
        $this->assertSame(
            'The session storage driver "redis" could not be resolved or is not registered.',
            $exception->getMessage()
        );
    }

    public function testReportsTheDriverNameThatCouldNotBeResolved(): void
    {
        $exception = new UnknownDriverException('file');

        $this->assertStringContainsString('"file"', $exception->getMessage());
    }
}
