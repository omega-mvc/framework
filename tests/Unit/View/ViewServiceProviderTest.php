<?php

declare(strict_types=1);

namespace Tests\View;

use Omega\Application\Application;
use Omega\Config\ConfigRepository;
use Omega\Http\Response;
use Omega\View\Exceptions\DirectiveNotRegisterException;
use Omega\View\Templator;
use Omega\View\Templator\DirectiveTemplator;
use Omega\View\TemplatorFinder;
use Omega\View\ViewServiceProvider;
use Omega\View\Vite;

use function Omega\Application\slash;


covers(ViewServiceProvider::class);

beforeEach(function (): void {
    $basePath = slash(__DIR__ . '/fixtures/support/');

    $this->app = new Application($basePath);
    $this->app->set('config', fn (): ConfigRepository => new ConfigRepository([
        'VIEW_EXTENSIONS' => ['.php'],
    ]));

    $this->app->set('path.public', $basePath . 'manifest/public');
    $this->app->set('paths.view', [$basePath . 'view']);
    $this->app->set('path.compiled_view_path', $basePath . 'cache');
});

afterEach(function (): void {
    DirectiveTemplator::reset();
    $this->app->flush();
});

it('boots the vite resolver bindings', function (): void {
    (new ViewServiceProvider($this->app))->boot();

    expect($this->app->get('vite.gets'))->toBeInstanceOf(Vite::class);
    expect($this->app->get('vite.location'))->toBeString();
    expect($this->app->get('vite.hasManifest'))->toBeTrue();
});

it('boots the view resolver bindings', function (): void {
    (new ViewServiceProvider($this->app))->boot();

    expect($this->app->get(TemplatorFinder::class))->toBeInstanceOf(TemplatorFinder::class);
    expect($this->app->get('view.instance'))->toBeInstanceOf(Templator::class);

    $resolver = $this->app->get('view.response');
    $this->assertIsCallable($resolver);

    $response = $resolver('test');
    $this->assertInstanceOf(Response::class, $response);
    expect($response->getContent())->toContain('omega');
});

it('registers the vite template directive', function (): void {
    (new ViewServiceProvider($this->app))->boot();

    $output = DirectiveTemplator::call('vite', ['resources/css/app.css']);

    expect($output)->toContain('fixtures/app-4ed993c7.css');
});

it('skips the vite directive when the vite resolver is not registered', function (): void {
    $provider = new class ($this->app) extends ViewServiceProvider {
        public function registerDirectives(): void
        {
            $this->registerViteDirectives();
        }
    };

    $provider->registerDirectives();

    expect(fn () => DirectiveTemplator::call('vite', ['resources/css/app.css']))
        ->toThrow(DirectiveNotRegisterException::class);
});