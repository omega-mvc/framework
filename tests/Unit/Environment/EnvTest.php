<?php

declare(strict_types=1);

namespace Tests\Environment;

use Omega\Environment\Env;
use ReflectionClass;
use Tests\FixturesPathTrait;

use function putenv;

covers(Env::class);

uses(FixturesPathTrait::class);

beforeEach(function (): void {
    $this->fixturePath = $this->setFixturePath('/fixtures/support/');
});

afterEach(function (): void {
    $valuesProp = (new ReflectionClass(Env::class))->getProperty('values');
    $valuesProp->setAccessible(true);
    $valuesProp->setValue(null, []);
});

it('can create immutable dotenv instance and load values', function (): void {
    Env::load($this->fixturePath, '.env.test');

    expect(Env::get('APP_NAME'))->toBe('Omega');
});

it('returns default value when key not found', function (): void {
    $default = 'default_value';

    expect(Env::get('NON_EXISTING_KEY', $default))->toBe($default);
});

it('converts string representations to native types', function (string $key, mixed $rawValue, mixed $expected): void {
    $valuesProp = (new ReflectionClass(Env::class))->getProperty('values');
    $valuesProp->setAccessible(true);
    $valuesProp->setValue(null, [$key => $rawValue]);

    expect(Env::get($key))->toBe($expected);
})->with([
    ['BOOL_TRUE', 'true', true],
    ['BOOL_FALSE', 'false', false],
    ['NULL_VAL', 'null', null],
    ['EMPTY_VAL', 'empty', ''],
    ['NUMERIC_INT', '42', 42],
    ['NUMERIC_FLOAT', '3.14', 3.14],
    ['NORMAL_STRING', 'Omega', 'Omega'],
    ['STRING_ALPHA', 'alpha', 'alpha'],
    ['STRING_ZERO', '0', 0],
    ['STRING_FLOAT_STRANGE', '10.50', 10.5],
    ['STRING_EMPTY_SPACE', ' ', ' '],
]);

it('returns non-string values as is', function (): void {
    $valuesProp = (new ReflectionClass(Env::class))->getProperty('values');
    $valuesProp->setAccessible(true);
    $valuesProp->setValue(null, [
        'ARRAY_VAL' => [1, 2, 3],
        'INT_VAL'   => 100,
        'BOOL_VAL'  => false,
        'NULL_VAL'  => null,
    ]);

    expect(Env::get('ARRAY_VAL'))->toEqual([1, 2, 3]);
    expect(Env::get('INT_VAL'))->toBe(100);
    expect(Env::get('BOOL_VAL'))->toBeFalse();
    expect(Env::get('NULL_VAL'))->toBeNull();
});

it('falls back to getenv', function (): void {
    putenv('SYSTEM_VAR=hello');
    expect(Env::get('SYSTEM_VAR'))->toBe('hello');
    putenv('SYSTEM_VAR');
});