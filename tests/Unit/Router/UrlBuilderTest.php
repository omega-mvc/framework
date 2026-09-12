<?php

declare(strict_types=1);

namespace Tests\Router;

use Omega\Router\Route;
use Omega\Router\Router;
use Omega\Router\RouteUrlBuilder;

covers(Route::class);
covers(Router::class);
covers(RouteUrlBuilder::class);

beforeEach(function (): void {
    $this->builder = new RouteUrlBuilder(Router::$patterns);
});

afterEach(function (): void {
    $this->builder = null;
});

it('can generate simple standard pattern', function (): void {
    expect($this->builder->buildUrl(
        new Route([
            'uri' => '/user/(:id)',
        ]),
        [123]
    ))->toBe('/user/123');
});

it('can generate multiple standard patterns', function (): void {
    expect($this->builder->buildUrl(
        new Route([
            'uri' => '/user/(:id)/profile/(:slug)',
        ]),
        [123, 'john-doe']
    ))->toBe('/user/123/profile/john-doe');
});

it('can generate with named parameters only', function (): void {
    expect($this->builder->buildUrl(
        new Route([
            'uri' => '/absensi/(identitas:id)/(tanggal:text)',
        ]),
        [
            'identitas' => 456,
            'tanggal'   => 'today',
        ]
    ))->toBe('/absensi/456/today');
});

it('can mix indexed and named parameters', function (): void {
    expect($this->builder->buildUrl(
        new Route([
            'uri' => '/user/(:id)/absensi/(identitas:id)/hari-ini',
        ]),
        [
            0           => 123,
            'identitas' => 456,
        ]
    ))->toBe('/user/123/absensi/456/hari-ini');
});

it('can generate with base path', function (): void {
    expect($this->builder->buildUrl(
        new Route([
            'uri' => '/admin/(section:text)/(userId:id)/edit',
        ]),
        [
            'section' => 'users',
            'userId'  => 999,
        ]
    ))->toBe('/admin/users/999/edit');
});

it('can generate with all pattern types', function (): void {
    expect($this->builder->buildUrl(
        new Route([
            'uri' => '/api/(:id)/(search:any)/page/(:num)/(filter:slug)',
        ]),
        [
            'id'     => 1,
            'search' => 'query_123',
            'num'    => 5,
            'filter' => 'active-users',
        ]
    ))->toBe('/api/1/query_123/page/5/active-users');
});

it('can generate with custom pattern', function (): void {
    expect($this->builder->buildUrl(
        new Route([
            'uri'      => '/color/(:hex)',
            'patterns' => ['(:hex)' => '([0-9a-fA-F]+)'],
        ]),
        ['ff00ff']
    ))->toBe('/color/ff00ff');
});

it('can handle zero and empty string values', function (): void {
    expect($this->builder->buildUrl(
        new Route([
            'uri' => '/user/(:id)/profile/(:text)',
        ]),
        [0, '']
    ))->toBe('/user/0/profile/');
});

it('can generate complex nested style', function (): void {
    expect($this->builder->buildUrl(
        new Route([
            'uri' => '/company/(:id)/employee/(empId:num)/profile/(:slug)/(avatar:text)',
        ]),
        [
            0        => 1,
            'empId'  => 456,
            1        => 'john-doe',
            'avatar' => 'large',
        ]
    ))->toBe('/company/1/employee/456/profile/john-doe/large');
});

it('can generate multiple same pattern types', function (): void {
    expect($this->builder->buildUrl(
        new Route([
            'uri' => '/tags/(:slug)/related/(:slug)',
        ]),
        ['php', 'laravel']
    ))->toBe('/tags/php/related/laravel');
});