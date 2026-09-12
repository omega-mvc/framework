<?php

declare(strict_types=1);

namespace Tests\View;

use Omega\View\Exceptions\ViewFileNotFoundException;
use Omega\View\Portal;
use Omega\View\View;
use ReflectionClass;
use Tests\FixturesPathTrait;

use function ob_get_clean;
use function ob_start;
use function str_replace;

uses(FixturesPathTrait::class);

covers(Portal::class);
covers(ViewFileNotFoundException::class);
covers(View::class);

it('can render using view classes', function (): void {
    $testHtml  = $this->setFixturePath('/fixtures/view/sample/sample.html');
    $testPhp   = $this->setFixturePath('/fixtures/view/sample/sample.php');

    ob_start();
    View::render($testHtml)->send();
    $renderHtml = ob_get_clean();

    ob_start();
    View::render($testPhp, ['contents' => ['say' => 'hay']])->send();
    $renderPhp = ob_get_clean();

    expect(str_replace("\r\n", "\n", (string) $renderHtml))->toEqual(
        "<html><head></head><body></body></html>\n"
    );

    expect(str_replace("\r\n", "\n", (string) $renderPhp))->toEqual(
        "<html><head></head><body><h1>hay</h1></body></html>\n"
    );
});

it('throw when file not found', function (): void {
    $this->expectException(ViewFileNotFoundException::class);
    View::render('unknown');
});

it('portal has method', function (): void {
    $data = [
        'auth' => ['user' => 'admin'],
        'meta' => ['title' => 'Home'],
        'contents' => ['say' => 'hello'],
    ];

    $viewPath = $this->setFixturePath('/fixtures/view/sample/sample.php');

    $response = View::render($viewPath, $data);

    $reflection = new ReflectionClass(View::class);
    $authProp = $reflection->getMethod('render')->getStaticVariables()['auth'] ?? null;

    $authPortal = new Portal($data['auth']);
    $metaPortal = new Portal($data['meta']);
    $contentPortal = new Portal($data['contents']);

    expect($authPortal->has('user'))->toBeTrue();
    expect($authPortal->has('unknown'))->toBeFalse();

    expect($metaPortal->has('title'))->toBeTrue();
    expect($metaPortal->has('unknown'))->toBeFalse();

    expect($contentPortal->has('say'))->toBeTrue();
    expect($contentPortal->has('other'))->toBeFalse();
});
