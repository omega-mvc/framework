<?php

declare(strict_types=1);

namespace Tests\Http;

use Omega\Router\Router;
use Omega\Testing\TestResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use Tests\TestCase;

use function Omega\Http\redirect;
use function Omega\Http\redirect_route;

#[CoversClass(Router::class)]
#[CoversClass(TestResponse::class)]
#[CoversFunction('Omega\Http\redirect')]
#[CoversFunction('Omega\Http\redirect_route')]
final class HelperTest extends TestCase
{
    public function testRedirectToCorrectUrl(): void
    {
        Router::get('/test/(:any)', fn ($test) => $test)->name('test');
        $redirect = redirect_route('test', ['ok']);
        $response = new TestResponse($redirect);
        $response->assertStatusCode(302);
        $response->assertSee('Redirecting to /test/ok');

        Router::reset();
    }

    public function testRedirectToCorrectUrlWithPlanUrl(): void
    {
        Router::get('/test', fn ($test) => $test)->name('test');
        $redirect = redirect_route('test');
        $response = new TestResponse($redirect);
        $response->assertStatusCode(302);
        $response->assertSee('Redirecting to /test');

        Router::reset();
    }

    public function testCanRedirectUsingGivenUrl(): void
    {
        $redirect = redirect('/test');
        $response = new TestResponse($redirect);
        $response->assertStatusCode(302);
        $response->assertSee('Redirecting to /test');
    }
}
