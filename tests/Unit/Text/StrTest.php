<?php

declare(strict_types=1);

namespace Tests\Text;

use Omega\Text\Exceptions\NoReturnException;
use Omega\Text\Str;

use function expect;

covers(Str::class);

it('return character specified position', function (): void {
    $text = 'i love laravel';
    expect(Str::charAt($text, 3))->toBe('o');
});

it('join two or more string into once', function (): void {
    $text = ['i', 'love', 'laravel'];
    expect(Str::concat($text))->toBe('i love laravel');
    expect(Str::concat($text, ' ', 'and'))->toBe('i love and laravel');
});

it('can find index of string', function (): void {
    $text = 'i love laravel';
    expect(Str::indexOf($text, 'l'))->toBe(2);
});

it('can find last index of string', function (): void {
    $text = 'i love laravel';
    expect(Str::lastIndexOf($text, 'l'))->toBe(13);
});

it('can find matches from pattern', function (): void {
    $text    = 'i love laravel';
    $matches = Str::match($text, '/love/');
    expect($matches)->toContain('love');

    $matches = Str::match($text, '/rust/');
    expect($matches)->toBeNull();
});

it('can search text', function (): void {
    $text = 'i love laravel';
    expect(Str::indexOf($text, 'laravel'))->toBe(7);
    expect(Str::indexOf($text, 'rust'))->toBeFalse();
});

it('can slice string', function (): void {
    $text = 'i love laravel';
    expect(Str::slice($text, 7))->toBe('laravel');
    expect(Str::slice($text, 7, 4))->toBe('lara');
    expect(Str::slice($text, 7, -1))->toBe('larave');
    expect(Str::slice($text, 15))->toBe('');
});

it('can split string', function (): void {
    $text = 'i love laravel';
    expect(Str::split($text, ' '))->toBe(['i', 'love', 'laravel']);
    expect(Str::split($text, ' ', 2))->toBe(['i', 'love laravel']);
});

it('can find and replace text', function (): void {
    $text = 'i love laravel';
    expect(Str::replace($text, 'laravel', 'php'))->toBe('i love php');
});

it('can uppercase string', function (): void {
    $text = 'i love laravel';
    expect(Str::toUpperCase($text))->toBe('I LOVE LARAVEL');
});

it('can lowercase string', function (): void {
    $text = 'I LOVE LARAVEL';
    expect(Str::toLowerCase($text))->toBe('i love laravel');
});

it('can uc first string', function (): void {
    $text = 'laravel';
    expect(Str::firstUpper($text))->toBe('Laravel');
});

it('can uc word string', function (): void {
    $text = 'i love laravel';
    expect(Str::firstUpperAll($text))->toBe('I Love Laravel');
});

it('can snake case', function (): void {
    expect(Str::toSnakeCase('i love laravel'))->toBe('i_love_laravel');
    expect(Str::toSnakeCase('i-love-laravel'))->toBe('i_love_laravel');
    expect(Str::toSnakeCase('i_love_laravel'))->toBe('i_love_laravel');
    expect(Str::toSnakeCase('i+love+laravel'))->toBe('i_love_laravel');
    expect(Str::toSnakeCase('i+love_laravel'))->toBe('i_love_laravel');
});

it('can kebab case', function (): void {
    expect(Str::toKebabCase('i love laravel'))->toBe('i-love-laravel');
    expect(Str::toKebabCase('i-love-laravel'))->toBe('i-love-laravel');
    expect(Str::toKebabCase('i_love_laravel'))->toBe('i-love-laravel');
    expect(Str::toKebabCase('i+love+laravel'))->toBe('i-love-laravel');
    expect(Str::toKebabCase('i+love_laravel'))->toBe('i-love-laravel');
});

it('can pascal case', function (): void {
    expect(Str::toPascalCase('i love laravel'))->toBe('ILoveLaravel');
    expect(Str::toPascalCase('i-love-laravel'))->toBe('ILoveLaravel');
    expect(Str::toPascalCase('i_love_laravel'))->toBe('ILoveLaravel');
    expect(Str::toPascalCase('i+love+laravel'))->toBe('ILoveLaravel');
    expect(Str::toPascalCase('i+love_laravel'))->toBe('ILoveLaravel');
});

it('can camel case', function (): void {
    expect(Str::toCamelCase('i love laravel'))->toBe('iLoveLaravel');
    expect(Str::toCamelCase('i-love-laravel'))->toBe('iLoveLaravel');
    expect(Str::toCamelCase('i_love_laravel'))->toBe('iLoveLaravel');
    expect(Str::toCamelCase('i+love+laravel'))->toBe('iLoveLaravel');
    expect(Str::toCamelCase('i+love_laravel'))->toBe('iLoveLaravel');
});

it('can detect text contain with', function (): void {
    $text = 'i love laravel';
    expect(Str::contains($text, 'laravel'))->toBeTrue();
    expect(Str::contains($text, 'symfony'))->toBeFalse();
});

it('can detect text starts with', function (): void {
    $text = 'i love laravel';
    expect(Str::startsWith($text, 'i'))->toBeTrue();
    expect(Str::startsWith($text, 'love'))->toBeFalse();
});

it('can detect text ends with', function (): void {
    $text = 'i love laravel';
    expect(Str::endsWith($text, 'laravel'))->toBeTrue();
    expect(Str::endsWith($text, 'love'))->toBeFalse();
});

it('can make slugify from text', function (): void {
    $text = 'i love laravel';
    expect(Str::slug($text))->toBe('i-love-laravel');
});

it('throws when slug does not return anything', function (): void {
    Str::slug('-~+-');
})->throws(NoReturnException::class, 'did not return anything');

it('can render template string', function (): void {
    $template = 'i love {lang}';
    $data     = ['lang' => 'laravel'];
    expect(Str::template($template, $data))->toBe('i love laravel');
});

it('can count text', function (): void {
    $text = 'i love laravel';
    expect(Str::length($text))->toBe(14);
});

it('a repeat text', function (): void {
    $text = 'Test';
    expect(Str::repeat($text, 3))->toBe('TestTestTest');
});

it('can detect string', function (): void {
    expect(Str::isString('text'))->toBeTrue();
});

it('can detect empty string', function (): void {
    expect(Str::isEmpty(''))->toBeTrue();
    expect(Str::isEmpty('test'))->toBeFalse();
});

it('can detect fill string in the start', function (): void {
    expect(Str::fill('1212', '0', 6))->toBe('001212');
});

it('can detect fill string in the end', function (): void {
    expect(Str::fillEnd('1212', '0', 6))->toBe('121200');
});

it('can make mask', function (): void {
    expect(Str::mask('laravel', '*', 1, 4))->toBe('l****el');
    expect(Str::mask('laravel', '*', 1))->toBe('l******');
    expect(Str::mask('laravel', '*', -3, 1))->toBe('lara*el');
    expect(Str::mask('laravel', '*', -3))->toBe('lara***');
});

it('can make limit', function (): void {
    expect(Str::limit('laravel best framework', 12))->toBe('laravel best...');
});

it('can get text after', function (): void {
    expect(Str::after('https://localhost:8000/test', ':'))->toBe(
        '//localhost:8000/test'
    );
});

it('can get text after must return back', function (): void {
    expect(Str::after('https://localhost:8000/test', '~'))->toBe(
        'https://localhost:8000/test'
    );
});
