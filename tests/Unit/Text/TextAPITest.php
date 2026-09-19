<?php

declare(strict_types=1);

namespace Tests\Text;

use Omega\Text\Exceptions\NoReturnException;
use Omega\Text\Text;

use function expect;

covers(Text::class);

beforeEach(function (): void {
    $this->text = new Text('i love symfony');
});

afterEach(function (): void {
    $this->text->reset();
});

it('can return chart at', function (): void {
    expect((string) $this->text->charAt(3))->toBe('o');
});

it('executes with a non string value', function (): void {
    $method = new \ReflectionMethod(Text::class, 'execute');
    $method->setAccessible(true);

    $method->invoke($this->text, ['foo'], 'test');

    expect($this->text->logs())->toHaveCount(2);
});

it('can return slice', function (): void {
    expect((string) $this->text->slice(7))->toBe('symfony');
});

it('does not throw when slice is not empty and throwOnFailure is enabled', function (): void {
    expect((string) $this->text->throwOnFailure(true)->slice(1))->toBe(' love symfony');
});

it('throws when slice returns empty and throwOnFailure is enabled', function (): void {
    $this->text->throwOnFailure(true)->slice(0, 0);
})->throws(NoReturnException::class, 'did not return anything');

it('can return lower', function (): void {
    expect((string) $this->text->lower())->toBe('i love symfony');
});

it('can return upper', function (): void {
    expect((string) $this->text->upper())->toBe('I LOVE SYMFONY');
});

it('can return first upper', function (): void {
    expect((string) $this->text->firstUpper())->toBe('I love symfony');
});

it('can return first upper all', function (): void {
    expect((string) $this->text->firstUpperAll())->toBe('I Love Symfony');
});

it('can return snake', function (): void {
    expect((string) $this->text->snake())->toBe('i_love_symfony');
});

it('can return kebab', function (): void {
    expect((string) $this->text->kebab())->toBe('i-love-symfony');
});

it('can return pascal', function (): void {
    expect((string) $this->text->pascal())->toBe('ILoveSymfony');
});

it('can return camel', function (): void {
    expect((string) $this->text->camel())->toBe('iLoveSymfony');
});

it('can return slug', function (): void {
    expect((string) $this->text->slug())->toBe('i-love-symfony');
});

it('can return is empty', function (): void {
    expect($this->text->isEmpty())->toBeFalse();
});

it('can return is', function (): void {
    expect($this->text->is('/^[A-Za-z]{1}[A-Za-z0-9]{3,16}$/'))->toBeFalse();
});

it('can return contains', function (): void {
    expect($this->text->contains('love'))->toBeTrue();
});

it('can return starts with', function (): void {
    expect($this->text->startsWith('i love'))->toBeTrue();
});

it('can return ends with', function (): void {
    expect($this->text->endsWith('symfony'))->toBeTrue();
});

it('can return length', function (): void {
    expect($this->text->length())->toBe(14);
});

it('can return index of', function (): void {
    expect($this->text->indexOf('symfony'))->toBe(7);
});

it('can return last index of', function (): void {
    expect($this->text->lastIndexOf('y'))->toBe(13);
});

it('returns false when last index of is not found', function (): void {
    expect($this->text->lastIndexOf('x'))->toBeFalse();
});

it('can return is match', function (): void {
    expect($this->text->isMatch('/^i love/'))->toBeTrue();
});

it('returns false when is match does not match', function (): void {
    expect($this->text->isMatch('/^z/'))->toBeFalse();
});

it('can return fill', function (): void {
    $this->text->text('1234');
    expect((string) $this->text->fill('0', 6))->toBe('001234');
});

it('can return fill end', function (): void {
    $this->text->text('1234');
    expect((string) $this->text->fillEnd('0', 6))->toBe('123400');
});

it('can return mask', function (): void {
    $this->text->text('laravel');
    expect((string) $this->text->mask('*', 1, 4))->toBe('l****el');

    $this->text->text('laravel');
    expect((string) $this->text->mask('*', 1))->toBe('l******');

    $this->text->text('laravel');
    expect((string) $this->text->mask('*', -3, 1))->toBe('lara*el');

    $this->text->text('laravel');
    expect((string) $this->text->mask('*', -3))->toBe('lara***');
});

it('can return limit', function (): void {
    expect((string) $this->text->limit(7))->toBe('i love ...');
});

it('can return after text', function (): void {
    expect($this->text->after('love ')->__toString())->toBe('symfony');
});
