<?php

declare(strict_types=1);

namespace Tests\Http;

use Exception;
use Omega\Application\Application;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Http\Http;
use Omega\Http\Request;
use Omega\Http\Response;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use Tests\FixturesPathTrait;
use Tests\Http\Support\TestKernelTerminate;

use function ob_get_clean;
use function ob_start;

uses(FixturesPathTrait::class);

covers(Application::class);
covers(BindingResolutionException::class);
covers(CircularAliasException::class);
covers(EntryNotFoundException::class);
covers(Http::class);
covers(Request::class);
covers(Response::class);

beforeEach(function (): void {
    $this->app = new Application($this->setFixtureBasePath());

    $this->app->set(
        Http::class,
        fn () => new $this->http($this->app)
    );

    $this->http = new class ($this->app) extends Http {
        public function handle(Request $request): Response
        {
            return new Response('ok');
        }

        protected function dispatcherMiddleware(Request $request): array
        {
            return [TestKernelTerminate::class];
        }
    };
});

afterEach(function (): void {
    $this->app->flush();
});

it('can terminate', function (): void {
    $http = $this->app->make(Http::class);

    if (!$http instanceof Http) {
        throw new Exception('Expected an Http instance from the container.');
    }

    $response = $http->handle(
        $request = new Request('/test')
    );

    $this->app->registerTerminate(static function () {
        echo 'terminated.';
    });

    ob_start();
    $http->terminate($request, $response);
    $out = ob_get_clean();

    expect($out)->toEqual('/testokterminated.');
});