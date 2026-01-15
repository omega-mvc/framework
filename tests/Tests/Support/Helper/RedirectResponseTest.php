<?php

declare(strict_types=1);

namespace Tests\Support\Helper;

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Omega\Router\Router;
use Omega\Testing\TestResponse;

#[CoversClass(Router::class)]
#[CoversClass(TestResponse::class)]
final class RedirectResponseTest extends TestCase
{
    /**
     * Test it redirect to correct url.
     *
     * @return void
     * @throws Exception
     */
    public function testItRedirectToCorrectUrl(): void
    {
        Router::get('/test/(:any)', fn ($test) => $test)->name('test');
        $redirect = redirect_route('test', ['ok']);
        $response = new TestResponse($redirect);
        $response->assertStatusCode(302);
        $response->assertSee('Redirecting to /test/ok');

        Router::reset();
    }

    /**
     * Test it redirect to correct url with plan url.
     *
     * @return void
     * @throws Exception
     */
    public function testItRedirectToCorrectUrlWithPlanUrl(): void
    {
        Router::get('/test', fn ($test) => $test)->name('test');
        $redirect = redirect_route('test');
        $response = new TestResponse($redirect);
        $response->assertStatusCode(302);
        $response->assertSee('Redirecting to /test');

        Router::reset();
    }

    /**
     * Test it can redirect using given url.
     *
     * @return void
     * @throws Exception
     */
    public function testItCanRedirectUsingGivenUrl(): void
    {
        $redirect = redirect('/test');
        $response = new TestResponse($redirect);
        $response->assertStatusCode(302);
        $response->assertSee('Redirecting to /test');
    }
}
