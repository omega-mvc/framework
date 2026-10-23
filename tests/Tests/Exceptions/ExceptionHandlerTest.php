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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\Exceptions\Support\LogStore;
use Throwable;

use function file;
use function str_contains;


#[CoversClass(Application::class)]
#[CoversClass(BindingResolutionException::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(EntryNotFoundException::class)]
#[CoversClass(ExceptionHandler::class)]
#[CoversClass(HttpException::class)]
#[CoversClass(Http::class)]
#[CoversClass(Request::class)]
#[CoversClass(Response::class)]
#[CoversClass(ApplicationManifest::class)]
#[CoversClass(Str::class)]
#[CoversClass(Templator::class)]
#[CoversClass(TemplatorFinder::class)]

class ExceptionHandlerTest extends \Tests\TestCase
{
    protected Http $http;

    protected ExceptionHandler $exceptionHandler;

    protected function setUp(): void {
    LogStore::reset();

    $this->app = new Application(__DIR__ . '/fixtures/application-read/');

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
}

    protected function tearDown(): void
    {
        $this->app->flush();
        LogStore::reset();
        HandleExceptions::resetHandlersState();
    }

    #[Test]
    public function test_can_render_an_exception(): void
    {
        $http = $this->app->make(Http::class);

        if (!$http instanceof Http) {
            throw new \RuntimeException('Expected an Http instance.');
        }

        $response = $http->handle(new Request('/test'));

        $this->assertSame('Too Many Request', $response->getContent());
        $this->assertSame(429, $response->getStatusCode());
    }

    #[Test]
    public function test_can_report_an_exception(): void
    {
        $http = $this->app->make(Http::class);

        if (!$http instanceof Http) {
            throw new \RuntimeException('Expected an Http instance.');
        }

        $http->handle(new Request('/test'));

        $this->assertEquals(['Too Many Request'], LogStore::all());
    }

    #[Test]
    public function test_can_render_an_exception_as_json(): void
    {
        $this->app->bootedCallback(fn () => $this->app->set('app.debug', false));

        $http = $this->app->make(Http::class);

        if (!$http instanceof Http) {
            throw new \RuntimeException('Expected an Http instance.');
        }

        $response = $http->handle(new Request('/test', [], [], [], [], [], [
            'content-type' => 'application/json',
        ]));

        $decoded = $response->getContent();
        $decoded = is_string($decoded) ? json_decode($decoded, true) : $decoded;
        $this->assertEquals([
            'code'     => 500,
            'messages' => [
                'message' => 'Internal Server Error',
            ],
        ], $decoded);
        $this->assertSame(429, $response->getStatusCode());
    }

    #[Test]
    public function test_can_render_an_exception_as_json_for_debug(): void
    {
        $this->app->bootedCallback(fn () => $this->app->set('app.debug', true));

        $http = $this->app->make(Http::class);

        if (!$http instanceof Http) {
            throw new \RuntimeException('Expected an Http instance.');
        }

        $response = $http->handle(new Request('/test', [], [], [], [], [], [
            'content-type' => 'application/json',
        ]));

        $content = $response->getContent();
        $content = is_string($content) ? json_decode($content, true) : $content;
        $this->assertIsArray($content);

        $messages = $content['messages'];
        $this->assertIsArray($messages);
        $this->assertSame('Too Many Request', $messages['message']);
        $this->assertSame('Omega\Http\Exceptions\HttpException', $messages['exception']);

        $reflection = new ReflectionMethod($this->http, 'dispatcher');
        $fileName = $reflection->getFileName();
        $source   = [];

        if ($fileName !== false) {
            $source = file($fileName) ?: [];
        }

        $expectedLine = null;

        foreach ($source as $number => $line) {
            if (str_contains((string) $line, 'throw new HttpException')) {
                $expectedLine = $number + 1;
                break;
            }
        }

        $this->assertNotNull($expectedLine, 'Unable to detect HttpException throw line dynamically.');
        $this->assertSame($expectedLine, $messages['line']);
        $this->assertSame(429, $response->getStatusCode());
    }

    #[Test]
    public function test_can_render_an_http_exception_through_a_view(): void
    {
        $app         = $this->app;
        $fixturePath = __DIR__ . '/fixtures/exceptions';

        $app->set('path.view', __DIR__ . '/fixtures/exceptions/');
        $app->set('paths.view', [
            __DIR__ . '/fixtures/exceptions/',
            __DIR__ . '/fixtures/exceptions/pages/',
        ]);
        $app->set(
            TemplatorFinder::class,
            fn () => new TemplatorFinder(
                array_map(fn (mixed $item) => is_string($item) ? $item : '', (array) ($app->get('paths.view') ?? [])),
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

        $this->assertTrue(Str::contains(is_string($content) ? $content : '', '<h1>Too Many Request</h1>'));
    }
}