<?php

declare(strict_types=1);

namespace Tests\Console\Traits;

use Omega\Console\Traits\InteractWithFilesystemTrait;
use Tests\Console\Fixtures\FilesystemProbe;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class InteractWithFilesystemTest extends TestCase
{

    public function testReturnsAnEmptyListWhenTheDirectoryDoesNotExist(): void
    {
        $probe = new FilesystemProbe();

        $this->assertSame([], $probe->find('/no/such/directory', '*'));
    }

    public function testMatchesFilesByASinglePattern(): void
    {
        $probe = new FilesystemProbe();
        $files = $probe->find(__DIR__ . '/../Fixtures/Files', '*.txt');

        $this->assertCount(1, $files);
        $this->assertStringEndsWith('a.txt', $files[0]);
    }

    public function testMatchesFilesByMultiplePatterns(): void
    {
        $probe = new FilesystemProbe();
        $files = $probe->find(__DIR__ . '/../Fixtures/Files', ['*.php', '*.log']);

        $this->assertCount(2, $files);
    }

    public function testMatchesEveryFileWithTheDefaultPattern(): void
    {
        $probe = new FilesystemProbe();
        $files = $probe->find(__DIR__ . '/../Fixtures/Files');

        $this->assertCount(3, $files);
    }

    public function testExcludesFilesMatchingTheGivenPatterns(): void
    {
        $probe = new FilesystemProbe();
        $files = $probe->find(__DIR__ . '/../Fixtures/Files', '*', ['*.log']);

        $this->assertCount(2, $files);
        $this->assertNotContains(__DIR__ . '/../Fixtures/Files/b.log', $files);
    }

}