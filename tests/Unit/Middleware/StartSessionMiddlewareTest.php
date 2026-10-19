<?php

declare(strict_types=1);

namespace Tests\Middleware;

use Omega\Http\Request;
use Omega\Http\Response;
use Omega\Middleware\StartSessionMiddleware;
use Omega\Session\SessionManager;
use Omega\Session\Storage\ArrayStorage;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(ArrayStorage::class)]
#[CoversClass(Request::class)]
#[CoversClass(Response::class)]
#[CoversClass(SessionManager::class)]
#[CoversClass(StartSessionMiddleware::class)]
final class StartSessionMiddlewareTest extends TestCase
{
    public function testStartsAndSavesTheSessionForEachRequest(): void
    {
        $storage    = new ArrayStorage();
        $session    = new SessionManager('array', $storage);
        $middleware = new StartSessionMiddleware($session);

        $session->setId('test-session-id');

        $handled = $middleware->handle(new Request('/'), function (Request $request) use ($session): Response {
            $session->put('name', 'omega');

            return new Response('content');
        });

        $this->assertSame('test-session-id', $session->getId());

        $content = $storage->read('test-session-id');
        $this->assertIsString($content);
        $this->assertStringContainsString('"name":"omega"', $content);

        $this->assertSame('content', $handled->getContent());
    }

    public function testReturnsTheResponseProducedByTheNextMiddleware(): void
    {
        $session    = new SessionManager('array', new ArrayStorage());
        $middleware = new StartSessionMiddleware($session);
        $response   = new Response('ok');

        $handled = $middleware->handle(new Request('/'), fn (Request $request) => $response);

        $this->assertSame($response, $handled);
    }
}
