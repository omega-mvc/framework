<?php

declare(strict_types=1);

namespace Tests\Middleware;

use InvalidArgumentException;
use Omega\Csrf\Csrf;
use Omega\Csrf\Exceptions\InvalidCsrfTokenException;
use Omega\Http\Request;
use Omega\Http\Response;
use Omega\Middleware\CsrfMiddleware;
use Omega\Session\SessionManager;
use Omega\Session\Storage\ArrayStorage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

#[CoversClass(ArrayStorage::class)]
#[CoversClass(Csrf::class)]
#[CoversClass(CsrfMiddleware::class)]
#[CoversClass(InvalidCsrfTokenException::class)]
#[CoversClass(Request::class)]
#[CoversClass(Response::class)]
#[CoversClass(SessionManager::class)]
final class CsrfMiddlewareTest extends TestCase
{
    private Csrf $csrf;

    private CsrfMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $session          = new SessionManager('array', new ArrayStorage());
        $this->csrf       = new Csrf($session);
        $this->middleware = new CsrfMiddleware($this->csrf);
    }

    /** @return array<int, array<int, string>> */
    public static function safeMethodProvider(): array
    {
        return [['GET'], ['HEAD'], ['OPTIONS']];
    }

    #[DataProvider('safeMethodProvider')]
    public function testPassesSafeRequestMethodsWithoutAToken(string $method): void
    {
        $response = new Response('ok');
        $handled  = $this->middleware->handle(
            new Request('/', [], [], [], [], [], [], $method),
            fn (Request $request) => $response
        );

        $this->assertSame($response, $handled);
    }

    public function testThrowsAnExceptionWhenAPostRequestLacksAToken(): void
    {
        $this->expectException(InvalidCsrfTokenException::class);

        $this->middleware->handle(
            new Request('/', [], [], [], [], [], [], 'POST'),
            fn (Request $request) => new Response('ok')
        );
    }

    public function testThrowsAnExceptionWhenAPostRequestSendsAnInvalidToken(): void
    {
        $this->expectException(InvalidCsrfTokenException::class);

        $this->middleware->handle(
            new Request('/', [], ['_csrf_token' => 'invalid'], [], [], [], [], 'POST'),
            fn (Request $request) => new Response('ok')
        );
    }

    public function testPassesAPostRequestCarryingAValidToken(): void
    {
        $token = $this->csrf->generateToken();

        $response = new Response('ok');
        $handled  = $this->middleware->handle(
            new Request('/', [], ['_csrf_token' => $token], [], [], [], [], 'POST'),
            fn (Request $request) => $response
        );

        $this->assertSame($response, $handled);
    }

    public function testThrowsAnExceptionWhenTheNextMiddlewareDoesNotReturnAResponse(): void
    {
        $token = $this->csrf->generateToken();

        $this->expectException(InvalidArgumentException::class);

        $this->middleware->handle(
            new Request('/', [], ['_csrf_token' => $token], [], [], [], [], 'POST'),
            fn (Request $request) => null
        );
    }
}
