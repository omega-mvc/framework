<?php

declare(strict_types=1);

namespace Tests\Application;

use InvalidArgumentException;
use Omega\Application\Application;
use Omega\Exceptions\ApplicationNotAvailableException;
use Tests\FixturesPathTrait;

use function Omega\Application\app;
use function Omega\Application\get_path;
use function Omega\Application\is_dev;
use function Omega\Application\is_production;
use function Omega\Application\os_detect;
use function Omega\Application\path;
use function Omega\Application\set_path;
use function Omega\Application\slash;

covers(Application::class);

uses(FixturesPathTrait::class);

it('throws an error after flushing the application', function (): void {
    $app = new Application('/');
    $app->flush();

    app();
})->throws(ApplicationNotAvailableException::class);

it('loads the application', function (): void {
    $app = new Application('');

    expect(app()->get('path.base'))->toBe('/');

    $app->flush();
});

it('resolves environment helpers', function (): void {
    $app = new Application($this->setFixtureBasePath());

    $app->set('environment', 'prod');
    expect(is_dev())->toBeFalse();
    expect(is_production())->toBeTrue();
});

it('detects all supported OS families', function (): void {
    expect(os_detect('Windows'))->toBe('windows');
    expect(os_detect('Linux'))->toBe('linux');
    expect(os_detect('Darwin'))->toBe('mac');
    expect(os_detect('Bsd'))->toBe('bsd');
    expect(os_detect('Solaris'))->toBe('solaris');
    expect(os_detect('AmigaOS'))->toBe('unknown');

    $currentOs = strtolower(PHP_OS_FAMILY);
    $expected  = match ($currentOs) {
        'darwin' => 'mac',
        'windows', 'linux', 'bsd', 'solaris' => $currentOs,
        default => 'unknown',
    };

    expect(os_detect())->toBe($expected);
});

it('handles both strings and arrays in slash', function (): void {
    $separator = DIRECTORY_SEPARATOR;

    expect(slash('a/b'))->toBe("a{$separator}b");
    expect(slash('c'))->toBe('c');

    $input    = ['a/b', 'c/d'];
    $expected = ["a{$separator}b", "c{$separator}d"];

    expect(slash($input))->toBe($expected);
});

it('normalizes paths', function (): void {
    $ds = DIRECTORY_SEPARATOR;

    expect(path('app.config'))->toBe("app{$ds}config{$ds}");
    expect(path("app.logs{$ds}"))->toBe("app{$ds}logs{$ds}");

    $input    = ['core.view', 'cache'];
    $expected = ["core{$ds}view{$ds}", "cache{$ds}"];
    expect(path($input))->toBe($expected);

    expect(path(''))->toBe("{$ds}");
});

it('gets a path with array and suffix', function (): void {
    $app = new Application(__DIR__);
    $ds  = DIRECTORY_SEPARATOR;

    $paths = [
        'logs'  => 'storage/logs/',
        'cache' => 'storage/framework/cache/',
    ];
    $app->set('custom_paths', $paths);

    $result = get_path('custom_paths', 'daily/');

    $expected = [
        'logs'  => "storage/logs/daily{$ds}",
        'cache' => "storage/framework/cache/daily{$ds}",
    ];

    expect($result)->toBe($expected);
});

it('gets a path with string and suffix', function (): void {
    $app = new Application(__DIR__);
    $ds  = DIRECTORY_SEPARATOR;

    $app->set('single_path', 'app/core/');

    $result = get_path('single_path', 'test/');

    expect($result)->toBe("app/core/test{$ds}");
});

it('gets a path with an array of string identifiers', function (): void {
    $app = new Application(__DIR__);
    $ds  = DIRECTORY_SEPARATOR;

    $app->set('a_path', 'a/');
    $app->set('b_path', 'b/');

    $result = get_path(['a_path', 'b_path'], 'x/');

    expect($result)->toBe(["a/x{$ds}", "b/x{$ds}"]);
});

it('gets a path with a non-string binding value', function (): void {
    $app = new Application(__DIR__);
    $ds  = DIRECTORY_SEPARATOR;

    $app->set('int_path', 42);

    $result = get_path('int_path', 'suf/');

    expect($result)->toBe("suf{$ds}");
});

it('gets a path with a non-string element inside an array value', function (): void {
    $app = new Application(__DIR__);
    $ds  = DIRECTORY_SEPARATOR;

    $app->set('mixed_path', ['str' => 'app/', 'num' => 42]);

    $result = get_path('mixed_path', 'suf/');

    expect($result)->toBe(['str' => "app/suf{$ds}", 'num' => "suf{$ds}"]);
});

it('gets a path with an array identifier containing an array value', function (): void {
    $app = new Application(__DIR__);

    $app->set('mixed_path', ['str' => 'app/', 'num' => 42]);

    $result = get_path(['mixed_path'], 'suf/');

    expect($result)->toBe(['']);
});

it('sets a path with a single string', function (): void {
    $ds      = DIRECTORY_SEPARATOR;
    $input   = 'app.config.storage';
    $expected = "{$ds}app{$ds}config{$ds}storage{$ds}";

    expect(set_path($input))->toBe($expected);
});

it('sets a path with an array of strings', function (): void {
    $ds       = DIRECTORY_SEPARATOR;
    $input    = ['app.config', 'public.assets'];
    $expected = [
        "{$ds}app{$ds}config{$ds}",
        "{$ds}public{$ds}assets{$ds}",
    ];

    expect(set_path($input))->toBe($expected);
});

it('throws an exception on an empty string path', function (): void {
    set_path('');
})->throws(InvalidArgumentException::class, 'The path key cannot be an empty string');

it('throws an exception on an empty array path', function (): void {
    set_path([]);
})->throws(InvalidArgumentException::class);
