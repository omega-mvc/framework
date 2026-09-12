<?php

declare(strict_types=1);

namespace Tests\Macroable;

use Closure;
use Omega\Macroable\Exceptions\MacroNotFoundException;
use Omega\Macroable\MacroableTrait;

use function get_class;

covers(MacroableTrait::class);
covers(MacroNotFoundException::class);

/**
 * @method bool          test()
 * @method bool          test_param(bool $bool)
 * @method object        whoAmI()
 * @method string        className()
 * @method string        upper(string $value)
 * @method mixed         missing()
 * @method static bool   test()
 * @method static bool   test_param(bool $bool)
 * @method static object whoAmI()
 * @method static string className()
 * @method static string upper(string $value)
 * @method static mixed  missing()
 */
class MacroableFixture
{
    use MacroableTrait;
}

final class MacroableScopeHelper
{
    public static function className(): Closure
    {
        return static function (): string {
            return static::class;
        };
    }
}

beforeEach(function (): void {
    $this->mockClass = new MacroableFixture();
});

afterEach(function (): void {
    $this->mockClass->resetMacro();
});

it('can add macro', function (): void {
    $this->mockClass->macro('test', fn (): bool => true);
    $this->mockClass->macro('test_param', fn (bool $bool): bool => $bool);

    expect($this->mockClass->test())->toBeTrue();
    expect($this->mockClass->test_param(true))->toBeTrue();
});

it('can add static macro', function (): void {
    $this->mockClass->macro('test', fn (): bool => true);
    $this->mockClass->macro('test_param', fn (bool $bool): bool => $bool);

    expect($this->mockClass::test())->toBeTrue();
    expect($this->mockClass::test_param(true))->toBeTrue();
});

it('can check macro', function (): void {
    $this->mockClass->macro('test', fn (): bool => true);

    expect($this->mockClass->hasMacro('test'))->toBeTrue();
    expect($this->mockClass->hasMacro('test2'))->toBeFalse();
});

it('throws when macro is not registered', function (): void {
    $this->mockClass->test();
})->throws(MacroNotFoundException::class);

it('binds instance macro to this', function (): void {
    $fixture = $this->mockClass;

    $fixture->macro('whoAmI', function () {
        return $this;
    });

    expect($fixture->whoAmI())->toBe($fixture);
});

it('binds static macro to class', function (): void {
    $this->mockClass->macro('className', MacroableScopeHelper::className());

    expect($this->mockClass::className())->toBe(get_class($this->mockClass));
});

it('supports non closure callable macros', function (): void {
    $this->mockClass->macro('upper', 'strtoupper');

    expect($this->mockClass->upper('ciao'))->toBe('CIAO');
    expect($this->mockClass::upper('ciao'))->toBe('CIAO');
});

it('shares macros across instances of same class', function (): void {
    $class = new class {
        use MacroableTrait;
    };

    $class::macro('foo', fn () => 'bar');

    $instance = new $class();

    expect($instance::hasMacro('foo'))->toBeTrue();
});

it('resets macros', function (): void {
    $this->mockClass::macro('foo', fn () => true);

    expect($this->mockClass::hasMacro('foo'))->toBeTrue();

    $this->mockClass::resetMacro();

    expect($this->mockClass::hasMacro('foo'))->toBeFalse();
});

it('throws on missing static macro', function (): void {
    $this->mockClass::missing();
})->throws(MacroNotFoundException::class);