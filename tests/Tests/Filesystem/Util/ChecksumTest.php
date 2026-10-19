<?php

declare(strict_types=1);

namespace Tests\Filesystem\Util;

use Omega\Filesystem\Util\Checksum;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use Tests\TestCase;

use function file_put_contents;
use function md5;
use function md5_file;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

#[CoversClass(Checksum::class)]
final class ChecksumTest extends TestCase
{
    public function testComputesMd5ChecksumFromContent(): void
    {
        $this->assertSame(md5('hello'), Checksum::fromContent('hello'));
    }

    public function testComputesMd5ChecksumFromEmptyContent(): void
    {
        $this->assertSame(md5(''), Checksum::fromContent(''));
    }

    public function testComputesMd5ChecksumFromAFile(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'omega_');
        file_put_contents($tmpFile, 'test content');

        try {
            $this->assertSame(md5_file($tmpFile), Checksum::fromFile($tmpFile));
        } finally {
            unlink($tmpFile);
        }
    }

    public function testThrowsRuntimeExceptionForNonExistentFile(): void
    {
        $this->expectException(RuntimeException::class);

        set_error_handler(static fn (): bool => true);

        try {
            Checksum::fromFile('/nonexistent/path/file.txt');
        } finally {
            restore_error_handler();
        }
    }
}
