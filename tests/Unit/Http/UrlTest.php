<?php

declare(strict_types=1);

namespace Tests\Http;

use Omega\Http\Request;
use Omega\Http\Url;

use function Omega\Application\path;

covers(Request::class);
covers(Url::class);
covers('Omega\Application\path');

it('url parse', function (): void {
    $url = Url::parse('http://username:password@hostname:9090/path?arg=value#anchor');

    expect($url->schema())->toEqual('http');
    expect($url->host())->toEqual('hostname');
    expect($url->port())->toEqual(9090);
    expect($url->user())->toEqual('username');
    expect($url->password())->toEqual('password');
    expect($url->path())->toEqual('/path');
    expect($url->query())->toEqual(['arg' => 'value']);
    expect($url->fragment())->toEqual('anchor');
});

it('url parse using request', function (): void {
    $request = new Request('http://username:password@hostname:9090/path?arg=value#anchor');
    $url     = Url::fromRequest($request);

    expect($url->schema())->toEqual('http');
    expect($url->host())->toEqual('hostname');
    expect($url->port())->toEqual(9090);
    expect($url->user())->toEqual('username');
    expect($url->password())->toEqual('password');
    expect($url->path())->toEqual('/path');
    expect($url->query())->toEqual(['arg' => 'value']);
    expect($url->fragment())->toEqual('anchor');
});

it('url parse missing schema', function (): void {
    $url = Url::parse('//www.example.com/path?googleguy=googley');

    expect($url->host())->toEqual('www.example.com');
    expect($url->path())->toEqual('/path');
    expect($url->query())->toEqual(['googleguy' => 'googley']);
});

it('can check url parse', function (): void {
    $url = Url::parse('http://username:password@hostname:9090/path?arg=value#anchor');

    expect($url->hasSchema())->toBeTrue();
    expect($url->hasHost())->toBeTrue();
    expect($url->hasPort())->toBeTrue();
    expect($url->hasUser())->toBeTrue();
    expect($url->hasPassword())->toBeTrue();
    expect($url->hasPath())->toBeTrue();
    expect($url->hasQuery())->toBeTrue();
    expect($url->hasFragment())->toBeTrue();
});

it('can check url parse missing schema', function (): void {
    $url = Url::parse('//www.example.com/path?googleguy=googley');

    expect($url->hasSchema())->toBeFalse();
    expect($url->hasHost())->toBeTrue();
    expect($url->hasPort())->toBeFalse();
    expect($url->hasUser())->toBeFalse();
    expect($url->hasPassword())->toBeFalse();
    expect($url->hasPath())->toBeTrue();
    expect($url->hasQuery())->toBeTrue();
    expect($url->hasFragment())->toBeFalse();
});