<?php

declare(strict_types=1);

namespace Tests\View;

use Omega\View\Exceptions\ViewFileNotFoundException;
use Omega\View\TemplatorFinder;


covers(ViewFileNotFoundException::class);
covers(TemplatorFinder::class);

it('can find templator file location', function (): void {
    $base = __DIR__ . '/fixtures/view/sample/Templators';

    $view = new TemplatorFinder([$base], ['.php']);

    expect($view->find('php'))->toEqual(
        __DIR__ . '/fixtures/view/sample/Templators/php.php'
    );
});

it('can find templator file location will throw', function (): void {
    $loader = __DIR__ . '/fixtures/view/sampleTemplators';

    $view = new TemplatorFinder([$loader], ['.php']);

    $this->expectException(ViewFileNotFoundException::class);
    $view->find('blade');
});

it('can find in path', function (): void {
    $loader = __DIR__ . '/fixtures/view/sample/Templators';

    $view = new TemplatorFinder([$loader], ['.php']);

    $this->assertEquals(
        __DIR__ . '/fixtures/view/sample/Templators/php.php',
        (fn () => $this->{'findInPath'}('php', [$loader]))->call($view)
    );
});

it('can find in path will throw exception', function (): void {
    $loader = __DIR__ . '/fixtures/view/Templators';

    $view = new TemplatorFinder([$loader], ['.php']);

    $this->expectException(ViewFileNotFoundException::class);
    (fn () => $this->{'findInPath'}('blade', [$loader]))->call($view);
});

it('find in path with empty paths throws', function (): void {
    $view = new TemplatorFinder([], ['.php']);

    $this->expectException(ViewFileNotFoundException::class);

    (fn () => $this->{'findInPath'}('php', []))->call($view);
});

it('find in path with no extensions throws', function (): void {
    $loader = __DIR__ . '/fixtures/view/sample/Templators';

    $view = new TemplatorFinder([$loader], []);

    $this->expectException(ViewFileNotFoundException::class);

    (fn () => $this->{'findInPath'}('php', [$loader]))->call($view);
});

it('can add path', function (): void {
    $loader = __DIR__ . '/fixtures/view/sample/Templators';

    $view = new TemplatorFinder([], ['.php']);
    $view->addPath($loader);

    expect($view->find('php'))->toEqual(
        __DIR__ . '/fixtures/view/sample/Templators/php.php'
    );
});

it('can set path', function (): void {
    $loader = __DIR__ . '/fixtures/view/sampleTemplators';

    $view  = new TemplatorFinder([], ['.php']);
    $paths = (fn () => $this->{'paths'})->call($view);
    expect($paths)->toEqual([]);
    $view->setPaths([$loader]);
    $paths = (fn () => $this->{'paths'})->call($view);
    expect($paths)->toEqual([$loader]);
});

it('set paths covers foreach with empty element', function (): void {
    $view = new TemplatorFinder([], ['.php']);

    $dummyPath = __DIR__;
    $view->setPaths([$dummyPath]);

    $paths = (fn() => $this->{'paths'})->call($view);
    expect($paths)->toEqual([realpath($dummyPath)]);
});

it('can not add multi path', function (): void {
    $loader = __DIR__ . '/fixtures/view/sampleTemplators';

    $view = new TemplatorFinder([], ['.php']);
    $view->addPath($loader);
    $view->addPath($loader);
    $view->addPath($loader);

    expect($view->getPaths())->toEqual([$loader]);
});

it('can add extension', function (): void {
    $loader = __DIR__ . '/fixtures/view/sample/Templators';

    $view = new TemplatorFinder([$loader]);
    $view->addExtension('.php');

    expect($view->find('php'))->toEqual(
        __DIR__ . '/fixtures/view/sample/Templators/php.php'
    );
});

it('can flush', function (): void {
    $loader = __DIR__ . '/fixtures/view/sample/Templators';

    $view = new TemplatorFinder([$loader], ['.php']);

    $view->find('php');
    /** @var array<string, string> $views */
    $views = (fn () => $this->{'views'})->call($view);
    expect($views)->toHaveCount(1);
    $view->flush();
    /** @var array<string, string> $views */
    $views = (fn () => $this->{'views'})->call($view);
    expect($views)->toHaveCount(0);
});

it('can get paths registered', function (): void {
    $loader = __DIR__ . '/fixtures/view/sampleTemplators';

    $view = new TemplatorFinder([$loader], ['.php']);

    expect($view->getPaths())->toEqual([$loader]);
});

it('can get extensions registered', function (): void {
    $loader = __DIR__ . '/fixtures/view/sampleTemplators';

    $view = new TemplatorFinder([$loader], ['.php']);

    expect($view->getExtensions())->toEqual(['.php']);
});
