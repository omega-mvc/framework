<?php

declare(strict_types=1);

namespace Tests\View;

use Exception;
use Omega\Application\Application;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Http\Response;
use Omega\Text\Str;
use Omega\View\Templator;
use Omega\View\TemplatorFinder;
use Omega\View\Vite;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use Tests\FixturesPathTrait;

use function Omega\View\view;
use function Omega\View\vite;

uses(FixturesPathTrait::class);

covers(Application::class);
covers('Omega\View\view');
covers('Omega\View\vite');

it('vite helper handles single and multiple entry points', function (): void {
    $app = new Application(__DIR__);
    $viteMock = $this->createMock(Vite::class);
    $app->set('vite.gets', $viteMock);

    $viteMock->expects($this->exactly(2))
    ->method('gets')
        ->willReturnOnConsecutiveCalls(
            ['main.js' => 'url_string'],
            ['a.js' => 'url_a', 'b.js' => 'url_b']
        );

    expect(vite('main.js'))->toBe('url_string');

    $resultArray = vite('a.js', 'b.js');
    $this->assertIsArray($resultArray);
    expect($resultArray)->toHaveCount(2);
    expect($resultArray['a.js'])->toBe('url_a');
});

it('can get response from container', function (): void {
    $app = new Application($this->setFixtureBasePath());

    $app->set(
        TemplatorFinder::class,
        fn () => new TemplatorFinder([$this->setFixturePath('/fixtures/support/view')], ['.php'])
    );

    $app->set(
        'view.instance',
        fn (TemplatorFinder $finder) => new Templator($finder, $this->setFixturePath('/fixtures/support/cache'))
    );

    $app->set(
        'view.response',
        fn () => function (string $viewPath, array $portal = []) use ($app): Response {
            /** @var Templator $templator */
            $templator = $app->make(Templator::class);

            /** @var array<string, mixed> $portal */
            return new Response($templator->render($viewPath, $portal));
        }
    );

    $view = view('test', [], ['status' => 500]);
    expect($view->getStatusCode())->toEqual(500);

    $content = $view->getContent();
    if (!is_string($content)) {
        $this->fail('Expected string view content.');
    }
    expect(Str::contains($content, 'omega'))->toBeTrue();

    $app->flush();
});
