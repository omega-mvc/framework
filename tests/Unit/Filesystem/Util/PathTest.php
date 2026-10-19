<?php

declare(strict_types=1);

namespace Tests\Filesystem\Util;

use Omega\Filesystem\Util\Path;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(Path::class)]
final class PathTest extends TestCase
{
    public function testNormalizesAPathWithDoubleDots(): void
    {
        $this->assertSame('/home/etc', Path::normalize('/home/user/docs/../../etc'));
    }

    public function testNormalizesAPathWithSingleDots(): void
    {
        $this->assertSame('/home/user/test', Path::normalize('/home/./user/test'));
    }

    public function testNormalizesAPathWithDoubleSlashes(): void
    {
        $this->assertSame('/home/user/test', Path::normalize('/home//user//test'));
    }

    public function testNormalizesAPathWithBackslashes(): void
    {
        $this->assertSame('c:/Users/test', Path::normalize('C:\\Users\\test'));
    }

    public function testNormalizesARelativePathWithDots(): void
    {
        $this->assertSame('other', Path::normalize('relative/../other'));
    }

    public function testNormalizesComplexPathWithMixedSegments(): void
    {
        $this->assertSame('/a/b/d', Path::normalize('/a/b/./c/../d'));
    }

    public function testHandlesRootPathWithDots(): void
    {
        $this->assertSame('/', Path::normalize('/..'));
    }

    public function testDetectsAbsoluteUnixPaths(): void
    {
        $this->assertTrue(Path::isAbsolute('/home/user'));
    }

    public function testDetectsRelativePaths(): void
    {
        $this->assertFalse(Path::isAbsolute('relative/path'));
    }

    public function testDetectsWindowsDriveLetterPaths(): void
    {
        $this->assertTrue(Path::isAbsolute('C:\\Users'));
    }

    public function testDetectsUncPaths(): void
    {
        $this->assertTrue(Path::isAbsolute('//server/share'));
    }

    public function testReturnsEmptyPrefixForRelativePaths(): void
    {
        $this->assertSame('', Path::getAbsolutePrefix('relative/path'));
    }

    public function testReturnsSlashPrefixForUnixPaths(): void
    {
        $this->assertSame('/', Path::getAbsolutePrefix('/home/user'));
    }

    public function testReturnsLowercaseDrivePrefixForWindowsPaths(): void
    {
        $this->assertSame('c:', Path::getAbsolutePrefix('C:\\Users'));
    }

    public function testReturnsDoubleSlashPrefixForUncPaths(): void
    {
        $this->assertSame('//', Path::getAbsolutePrefix('//server/share'));
    }

    public function testReturnsNormalizedDirname(): void
    {
        $this->assertSame('/a/b', Path::dirname('/a/b/c.txt'));
    }

    public function testNormalizesBackslashesInDirname(): void
    {
        $this->assertSame('C:/a/b', Path::dirname('C:\\a\\b\\c.txt'));
    }
}
