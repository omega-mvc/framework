<?php

declare(strict_types=1);

namespace Tests\Http;

use Exception;
use Omega\Http\Request;
use Omega\Http\Response;
use Omega\Text\Str;

use function json_decode;
use function ob_get_clean;
use function ob_start;
use function rand;

covers(Request::class);
covers(Response::class);
covers(Str::class);

beforeEach(function (): void {
    $html = '<html lang="en"><head></head><body></body></html>';
    $json = [
        'status'  => 'ok',
        'code'    => 200,
        'data'    => null,
    ];

    $this->htmlResponse = new Response($html, 200, []);
    $this->jsonResponse = new Response($json, 200, []);
});

it('render html response', function (): void {
    ob_start();
    $this->htmlResponse->html()->send();
    $html = ob_get_clean();

    expect($html)->toEqual(
        '<html lang="en"><head></head><body></body></html>'
    );
});

it('render json response', function (): void {
    ob_start();
    $this->jsonResponse->json()->send();
    $json = ob_get_clean();

    $this->assertIsString($json);

    expect(json_decode($json))->not->toBeNull();
    expect(json_decode($json, true))->toEqual(
        [
            'status'  => 'ok',
            'code'    => 200,
            'data'    => null,
        ]
    );
});

it('can be edited content', function (): void {
    $this->htmlResponse->setContent('edited');

    ob_start();
    $this->htmlResponse->html()->send();
    $html = ob_get_clean();

    expect($html)->toEqual(
        'edited'
    );
});

it('can set header using construct header', function (): void {
    $res = new Response('content', 200, ['test' => 'test']);

    $get_header = $res->getHeaders()['test'];

    expect($get_header)->toEqual('test');
});

it('can set header using set headers', function (): void {
    $res = new Response('content');
    $res->setHeaders(['test' => 'test']);

    $get_header = $res->getHeaders()['test'];

    expect($get_header)->toEqual('test');
});

it('can set header using header', function (): void {
    $res = new Response('content');
    $res->header('test', 'test');

    $get_header = $res->getHeaders()['test'];

    expect($get_header)->toEqual('test');
});

it('can set header using header and sanitizer header', function (): void {
    $res = new Response('content');
    $res->header('test : test:ok');

    $get_header = $res->getHeaders()['test'];

    expect($get_header)->toEqual('test:ok');
});

it('can set header using follow request', function (): void {
    $req = new Request('test', [], [], [], [], [], ['test' => 'test']);
    $res = new Response('content');

    $res->followRequest($req, ['test']);
    $get_header = $res->getHeaders()['test'];

    expect($get_header)->toEqual('test');
});

it('can get response status code', function (): void {
    $res = new Response('content', 200);

    expect($res->getStatusCode())->toEqual(200);
});

it('can get response content', function (): void {
    $res = new Response('content', 200);

    expect($res->getContent())->toEqual('content');
});

it('can get type of response code', function (): void {
    $res = new Response('content', rand(100, 199));
    expect($res->isInformational())->toBeTrue();

    $res = new Response('content', rand(200, 299));
    expect($res->isSuccessful())->toBeTrue();

    $res = new Response('content', rand(300, 399));
    expect($res->isRedirection())->toBeTrue();

    $res = new Response('content', rand(400, 499));
    expect($res->isClientError())->toBeTrue();

    $res = new Response('content', rand(500, 599));
    expect($res->isServerError())->toBeTrue();
});

it('can change protocol version', function (): void {
    $res = new Response('content');
    $res->setProtocolVersion('1.0');

    expect(Str::contains((string) $res, '1.0'))->toBeTrue();
});