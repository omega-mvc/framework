<?php

declare(strict_types=1);

namespace Tests\Filesystem\Util;

use InvalidArgumentException;
use Omega\Filesystem\Util\Size;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

use function file_put_contents;
use function fclose;
use function fopen;
use function fwrite;
use function rewind;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

#[CoversClass(Size::class)]
final class SizeTest extends TestCase
{
    public function testComputesSizeFromContent(): void
    {
        $this->assertSame(5, Size::fromContent('hello'));
    }

    public function testComputesSizeFromEmptyContent(): void
    {
        $this->assertSame(0, Size::fromContent(''));
    }

    public function testComputesSizeFromAFile(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'omega_');
        file_put_contents($tmpFile, 'test');

        try {
            $this->assertSame(4, Size::fromFile($tmpFile));
        } finally {
            unlink($tmpFile);
        }
    }

    public function testThrowsInvalidArgumentExceptionForNonExistentFile(): void
    {
        $this->expectException(InvalidArgumentException::class);

        set_error_handler(static fn (): bool => true);

        try {
            Size::fromFile('/nonexistent/path/file.txt');
        } finally {
            restore_error_handler();
        }
    }

    public function testComputesSizeFromAResource(): void
    {
        $handle = fopen('php://memory', 'r+');
        $this->assertIsResource($handle);
        fwrite($handle, 'test content');
        rewind($handle);

        $size = Size::fromResource($handle);
        $this->assertIsInt($size);
        fclose($handle);
    }
}
