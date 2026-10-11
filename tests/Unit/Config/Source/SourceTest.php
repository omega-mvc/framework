<?php

declare(strict_types=1);

namespace Tests\Config\Source;

use Omega\Config\Exceptions\FileReadException;
use Omega\Config\Source\AbstractSource;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(AbstractSource::class)]
#[CoversClass(FileReadException::class)]
final class SourceTest extends TestCase
{
    public function testShouldFetchFileContent(): void
    {
        $source = new TestConfigurationSource(__DIR__ . '/../fixtures/config/content.txt');

        $this->assertEquals(['content' => 'content'], $source->fetch());
    }

    public function testShouldThrowIfFileNotReadable(): void
    {
        $this->expectException(FileReadException::class);

        new TestConfigurationSource(__DIR__ . '/../fixtures/config/not-found.txt')->fetch();
    }

    public function testShouldThrowIfFileContentCannotBeRead(): void
    {
        $path   = sys_get_temp_dir() . '/omega-config-source-' . uniqid() . '.sock';
        $socket = stream_socket_server('unix://' . $path);

        if (false === $socket) {
            $this->markTestSkipped('Unable to create a unix socket.');
        }

        $this->expectException(FileReadException::class);

        try {
            set_error_handler(static fn (): bool => true);
            try {
                new TestConfigurationSource($path)->fetch();
            } finally {
                restore_error_handler();
            }
        } finally {
            fclose($socket);
            unlink($path);
        }
    }

    public function testShouldReturnAnEmptyArrayWhenTheValueIsNotAnArray(): void
    {
        $source = new TestConfigurationSource(__DIR__ . '/../fixtures/config/content.txt');

        $normalized = (fn () => $this->{'normalizeConfig'}('not-an-array'))->call($source);

        $this->assertSame([], $normalized);
    }
}
