<?php

declare(strict_types=1);

namespace Tests\Http;

use Omega\Http\RedirectResponse;
use Omega\Testing\TestResponse;

covers(RedirectResponse::class);
covers(TestResponse::class);

it('can get response content', function (): void {
    $res      = new RedirectResponse('/login');
    $redirect = new TestResponse($res);

    $redirect->assertSee('Redirecting to /login');
    $redirect->assertStatusCode(302);

    foreach ($res->getHeaders() as $key => $value) {
        if ('Location' === $key) {
            expect($value)->toEqual('/login');
        }
    }
});