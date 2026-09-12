<?php

declare(strict_types=1);

namespace Tests\Http;

use Exception;
use Omega\Http\JsonResponse;

use function json_decode;
use function ob_get_clean;
use function ob_start;

use const JSON_FORCE_OBJECT;
use const JSON_HEX_AMP;
use const JSON_HEX_APOS;
use const JSON_HEX_QUOT;
use const JSON_HEX_TAG;

it('can render json string', function (): void {
    $response = new JsonResponse([
        'language' => 'php',
        'ver'      => 80,
    ]);

    ob_start();
    $response->send();
    $json = ob_get_clean();

    $this->assertIsString($json);

    expect(json_decode($json))->not->toBeNull();
    $data = json_decode($json, true);
    expect($response->getContent())->toEqual('{"language":"php","ver":80}');
    expect($response->getData())->toEqual($data);
    expect($response->getStatusCode())->toEqual(200);
    expect($response->getContentType())->toEqual('application/json');
});

it('will throws invalid exception', function (): void {
    $this->expectExceptionMessageIsOrContains('Invalid encode data.');
    new JsonResponse(['say' => "Hello \x80 World"]);
});

it('constructor empty creates json object', function (): void {
    $response = new JsonResponse();
    expect($response->getContent())->toBe('{}');
});

it('constructor with array creates json array', function (): void {
    $response = new JsonResponse([0, 1, 2, 3]);
    expect($response->getContent())->toBe('[0,1,2,3]');
});

it('set json', function (): void {
    $response = new JsonResponse();
    $response->setJson('1');
    expect($response->getContent())->toEqual('1');

    $response = new JsonResponse();
    $response->setJson('true');
    expect($response->getContent())->toEqual('true');
});

it('json encode flags', function (): void {
    $response = new JsonResponse();
    $response->setData('<>\'&"');

    expect($response->getContent())->toEqual('"\u003C\u003E\u0027\u0026\u0022"');
});

it('get encoding options', function (): void {
    $response = new JsonResponse();

    expect($response->getEncodingOptions())->toEqual(
        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    );
});

it('can set encoding options', function (): void {
    $response = new JsonResponse();
    $response->setData([[1, 2, 3]]);

    expect($response->getContent())->toEqual('[[1,2,3]]');

    $response->setEncodingOptions(JSON_FORCE_OBJECT);

    expect($response->getContent())->toEqual('{"0":{"0":1,"1":2,"2":3}}');
});