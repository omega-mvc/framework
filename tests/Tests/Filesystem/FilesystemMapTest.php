<?php

declare(strict_types=1);

namespace Tests\Filesystem;

use InvalidArgumentException;
use Omega\Filesystem\Adapter\Memory\InMemory;
use Omega\Filesystem\Filesystem;
use Omega\Filesystem\FilesystemMap;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(FilesystemMap::class)]
final class FilesystemMapTest extends TestCase
{
    private FilesystemMap $map;

    private Filesystem $fs;

    protected function setUp(): void
    {
        parent::setUp();

        $this->map = new FilesystemMap();
        $this->fs  = new Filesystem(new InMemory());
    }

    public function testSetsAndGetsAFilesystem(): void
    {
        $this->map->set('local', $this->fs);
        $this->assertSame($this->fs, $this->map->get('local'));
    }

    public function testChecksIfAFilesystemExists(): void
    {
        $this->assertFalse($this->map->has('local'));
        $this->map->set('local', $this->fs);
        $this->assertTrue($this->map->has('local'));
    }

    public function testReturnsAllRegisteredFilesystems(): void
    {
        $this->map->set('fs1', $this->fs);
        $all = $this->map->all();
        $this->assertCount(1, $all);
    }

    public function testRemovesAFilesystem(): void
    {
        $this->map->set('local', $this->fs);
        $this->map->remove('local');
        $this->assertFalse($this->map->has('local'));
    }

    public function testClearsAllFilesystems(): void
    {
        $this->map->set('fs1', $this->fs);
        $this->map->clear();
        $this->assertSame([], $this->map->all());
    }

    public function testThrowsOnInvalidName(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->map->set('invalid name!', $this->fs);
    }

    public function testThrowsWhenGettingNonExistentFilesystem(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->map->get('missing');
    }

    public function testThrowsWhenRemovingNonExistentFilesystem(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->map->remove('missing');
    }

    public function testAcceptsHyphensUnderscoresAndNumbersInName(): void
    {
        $this->map->set('my-fs_1', $this->fs);
        $this->assertTrue($this->map->has('my-fs_1'));
    }
}
