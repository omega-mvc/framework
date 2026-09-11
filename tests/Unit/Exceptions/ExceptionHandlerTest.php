<?php

declare(strict_types=1);

namespace Tests\Exceptions;

use Omega\Application\Application;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Exceptions\ExceptionHandler;
use Omega\Exceptions\Bootstrapper\HandleExceptions;
use Omega\Http\Exceptions\HttpException;
use Omega\Http\Http;
use Omega\Http\Request;
use Omega\Http\Response;
use Omega\Application\ApplicationManifest;
use Omega\Application\Bootstrapper\BootProviders;
use Omega\Application\Bootstrapper\RegisterProviders;
use Omega\Config\Bootstrapper\ConfigBootstrapper;
use Omega\Facade\Bootstrapper\FacadeBootstrapper;
use Omega\Text\Str;
use Omega\View\Templator;
use Omega\View\TemplatorFinder;
use ReflectionMethod;
use Tests\Exceptions\Support\LogStore;
use Tests\FixturesPathTrait;
use Throwable;

use function file;
use function str_contains;

uses(FixturesPathTrait::class);

covers(Application::class);
covers(BindingResolutionException::class);
covers(CircularAliasException::class);
covers(EntryNotFoundException::class);
covers(ExceptionHandler::class);
covers(HttpException::class);
covers(Http::class);
covers(Request::class);
covers(Response::class);
covers(ApplicationManifest::class);
covers(Str::class);
covers(Templator::class);
covers(TemplatorFinder::class);

beforeEach(function (): void {
    LogStore::reset();

    $this->app = new Application($this->setFixturePath('/fixtures/application-read/'));

    $this->app->set('environment', 'testing');

    $this->app->set(ApplicationManifest::class, fn () => new ApplicationManifest(
        basePath: is_string($this->app->get('path.base')) ? $this->app->get('path.base') : '',
        applicationCachePath: $this->app->getApplicationCachePath(),
        vendorPath: '/package/'
    ));

    $this->app->set(
        Http::class,
        fn () => new $this->http($this->app)
    );

    $this->app->set(
        ExceptionHandler::class,
        fn () => $this->exceptionHandler
    );

    $this->http = new class ($this->app) extends Http {
        protected array $bootstrappers = [
            ConfigBootstrapper::class,
            FacadeBootstrapper::class,
            RegisterProviders::class,
            BootProviders::class,
        ];

        protected function dispatcher(Request $request): array
        {
            throw new HttpException(429, 'Too Many Request');
        }
    };

    $this->exceptionHandler = new class ($this->app) extends ExceptionHandler {
        public function render(Request $request, Throwable $th): Response
        {
            if ($request->isJson()) {
                return $this->handleJsonResponse($th);
            }

            if ($th instanceof HttpException) {
                return new Response($th->getMessage(), $th->getStatusCode(), $th->getHeaders());
            }

            return parent::render($request, $th);
        }

        public function report(Throwable $th): void
        {
            LogStore::push($th->getMessage());
        }
    };
});

afterEach(function (): void {
    $this->app->flush();
    LogStore::reset();
    HandleExceptions::resetHandlersState();
});

it('can render an exception', function (): void {
    $http = $this->app->make(Http::class);

    if (!$http instanceof Http) {
        throw new \RuntimeException('Expected an Http instance.');
    }

    $response = $http->handle(new Request('/test'));

    expect($response->getContent())->toBe('Too Many Request');
    expect($response->getStatusCode())->toBe(429);
});

it('can report an exception', function (): void {
    $http = $this->app->make(Http::class);

    if (!$http instanceof Http) {
        throw new \RuntimeException('Expected an Http instance.');
    }

    $http->handle(new Request('/test'));

    expect(LogStore::all())->toEqual(['Too Many Request']);
});

it('can render an exception as json', function (): void {
    $this->app->bootedCallback(fn () => $this->app->set('app.debug', false));

    $http = $this->app->make(Http::class);

    if (!$http instanceof Http) {
        throw new \RuntimeException('Expected an Http instance.');
    }

    $response = $http->handle(new Request('/test', [], [], [], [], [], [
        'content-type' => 'application/json',
    ]));

    expect($response->getContent())->toEqual([
        'code'     => 500,
        'messages' => [
            'message' => 'Internal Server Error',
        ],
    ]);
    expect($response->getStatusCode())->toBe(429);
});

it('can render an exception as json for debug', function (): void {
    $this->app->bootedCallback(fn () => $this->app->set('app.debug', true));

    $http = $this->app->make(Http::class);

    if (!$http instanceof Http) {
        throw new \RuntimeException('Expected an Http instance.');
    }

    $response = $http->handle(new Request('/test', [], [], [], [], [], [
        'content-type' => 'application/json',
    ]));

    $content = $response->getContent();
    $this->assertIsArray($content);

    $messages = $content['messages'];
    $this->assertIsArray($messages);

    expect($messages['message'])->toBe('Too Many Request');
    expect($messages['exception'])->toBe('Omega\Http\Exceptions\HttpException');

    $reflection = new ReflectionMethod($this->http, 'dispatcher');
    $fileName   = $reflection->getFileName();
    $source     = $fileName !== false ? file($fileName) : [];

    $expectedLine = null;

    foreach (($source ?: []) as $number => $line) {
        if (str_contains((string) $line, 'throw new HttpException')) {
            $expectedLine = $number + 1;
            break;
        }
    }

    $this->assertNotNull($expectedLine, 'Unable to detect HttpException throw line dynamically.');

    expect($messages['line'])->toBe($expectedLine);
    expect($response->getStatusCode())->toBe(429);
});

it('can render an http exception through a view', function (): void {
    $app         = $this->app;
    $fixturePath = $this->setFixturePath('/fixtures/exceptions');

    $app->set('path.view', $this->setFixturePath('/fixtures/exceptions/'));
    $app->set('paths.view', [
        $this->setFixturePath('/fixtures/exceptions/'),
        $this->setFixturePath('/fixtures/exceptions/pages/'),
    ]);
    $app->set(
        TemplatorFinder::class,
        fn () => new TemplatorFinder(
            array_map(fn ($item) => is_string($item) ? $item : '', (array) ($app->get('paths.view') ?? [])),
            ['.php', '.template.php']
        )
    );

    $app->set(
        'view.instance',
        fn (TemplatorFinder $finder) => new Templator($finder, $fixturePath)
    );

    $app->set(
        'view.response',
        fn () => function (string $viewPath, array $portal = []) use ($app, $fixturePath): Response {
            $templator = $app->make('view.instance');

            if (!$templator instanceof Templator) {
                $finder = $app->make(TemplatorFinder::class);
                $templator = $finder instanceof TemplatorFinder
                    ? new Templator($finder, $fixturePath)
                    : new Templator($fixturePath, $fixturePath);
            }

            /** @var array<string, mixed> $portal */
            return new Response((string) $templator->render($viewPath, $portal));
        }
    );

    $app->set(ExceptionHandler::class, fn () => new ExceptionHandler($app));

    $handler = $app->make(ExceptionHandler::class);

    if (!$handler instanceof ExceptionHandler) {
        throw new \RuntimeException('Expected an ExceptionHandler instance.');
    }

    $exception = new HttpException(429, 'Internal Error', null, []);
    $render    = $handler->render(new Request('/test'), $exception);

    $content = $render->getContent();

    expect(Str::contains(is_string($content) ? $content : '', '<h1>Too Many Request</h1>'))->toBeTrue();
});