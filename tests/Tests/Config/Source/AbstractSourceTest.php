<?php

declare(strict_types=1);

namespace Tests\Config\Source;

use Omega\Config\Exceptions\FileReadException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function compact;

class AbstractSourceTest extends TestCase
{
    public function testShouldFetchFileContent(): void
    {
        $source  = new TestConfigurationSource(slash(path: __DIR__ . '/fixtures/content.txt'));
        $content = "content";

        $this->assertEquals(compact('content'), $source->fetch());
    }

    public function testShouldThrowIfFileNotReadable(): void
    {
        $this->expectException(FileReadException::class);

        new TestConfigurationSource(slash(path: __DIR__ . '/fixtures/not-found.txt'))->fetch();
    }
}
