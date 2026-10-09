<?php

declare(strict_types=1);

namespace Tests\Http;

use Omega\Application\Application;
use Omega\Http\MacroServiceProvider;
use Omega\Http\Request;
use Omega\Http\Upload\UploadFile;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(Application::class)]
#[CoversClass(MacroServiceProvider::class)]
#[CoversClass(Request::class)]
final class MacroServiceProviderTest extends TestCase
{
    public function testRegistersRequestMacros(): void
    {
        $provider = new MacroServiceProvider(new Application(''));

        $provider->register();

        $this->assertTrue(Request::hasMacro('validate'));
        $this->assertTrue(Request::hasMacro('upload'));
    }

    public function testUploadMacroExecutesCorrectly(): void
    {
        $provider = new MacroServiceProvider(new Application(''));
        $provider->register();

        $mockFiles = [
            'avatar' => [
                'name'     => 'test.jpg',
                'type'     => 'image/jpeg',
                'tmp_name' => '/tmp/php_mock_file_123', // Un percorso inventato!
                'error'    => 0,
                'size'     => 1024,
            ]
        ];

        $request = new Request(
            url: '/',
            files: $mockFiles
        );

        $uploadFile = $request->upload('avatar');

        $this->assertInstanceOf(UploadFile::class, $uploadFile);
    }
}
