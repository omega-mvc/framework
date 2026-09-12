<?php

declare(strict_types=1);

namespace Tests\Http;

use Omega\Application\Application;
use Omega\Http\MacroServiceProvider;
use Omega\Http\Request;
use Omega\Http\Upload\UploadFile;

covers(Application::class);
covers(MacroServiceProvider::class);
covers(Request::class);

it('registers request macros', function (): void {
    $provider = new MacroServiceProvider(new Application(''));

    $provider->register();

    expect(Request::hasMacro('validate'))->toBeTrue();
    expect(Request::hasMacro('upload'))->toBeTrue();
});

it('upload macro executes correctly', function (): void {
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
});