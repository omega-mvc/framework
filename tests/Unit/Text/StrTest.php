<?php

declare(strict_types=1);

namespace Tests\Text;

use Omega\Text\Exceptions\NoReturnException;
use Omega\Text\Str;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class StrTest extends TestCase
{


    public function testReturnCharacterSpecifiedPosition(): void
    {
        $text = 'i love laravel';

        $this->assertSame('o', Str::charAt($text, 3));
    }

    public function testJoinTwoOrMoreStringIntoOnce(): void
    {
        $text = ['i', 'love', 'laravel'];

        $this->assertSame('i love laravel', Str::concat($text));
        $this->assertSame('i love and laravel', Str::concat($text, ' ', 'and'));
    }

    public function testCanFindIndexOfString(): void
    {
        $text = 'i love laravel';

        $this->assertSame(2, Str::indexOf($text, 'l'));
    }

    public function testCanFindLastIndexOfString(): void
    {
        $text = 'i love laravel';

        $this->assertSame(13, Str::lastIndexOf($text, 'l'));
    }

    public function testCanFindMatchesFromPattern(): void
    {
        $text    = 'i love laravel';
        $matches = Str::match($text, '/love/');

        $this->assertNotNull($matches);
        $this->assertContains('love', $matches);

        $matches = Str::match($text, '/rust/');

        $this->assertNull($matches);
    }

    public function testCanSearchText(): void
    {
        $text = 'i love laravel';

        $this->assertSame(7, Str::indexOf($text, 'laravel'));
        $this->assertFalse(Str::indexOf($text, 'rust'));
    }

    public function testCanSearchTextWithSearchMethod(): void
    {
        $text = 'i love laravel';

        $this->assertSame(7, Str::search($text, 'laravel'));
        $this->assertFalse(Str::search($text, 'rust'));
    }

    public function testCanSliceString(): void
    {
        $text = 'i love laravel';

        $this->assertSame('laravel', Str::slice($text, 7));
        $this->assertSame('lara', Str::slice($text, 7, 4));
        $this->assertSame('larave', Str::slice($text, 7, -1));
        $this->assertSame('', Str::slice($text, 15));
    }

    public function testCanSplitString(): void
    {
        $text = 'i love laravel';

        $this->assertSame(['i', 'love', 'laravel'], Str::split($text, ' '));
        $this->assertSame(['i', 'love laravel'], Str::split($text, ' ', 2));
    }

    public function testCanSplitStringIntoCharactersWithEmptySeparator(): void
    {
        $this->assertSame(['a', 'b', 'c'], Str::split('abc'));
    }

    public function testCanFindAndReplaceText(): void
    {
        $text = 'i love laravel';

        $this->assertSame('i love php', Str::replace($text, 'laravel', 'php'));
    }

    public function testCanUppercaseString(): void
    {
        $text = 'i love laravel';

        $this->assertSame('I LOVE LARAVEL', Str::toUpperCase($text));
    }

    public function testCanLowercaseString(): void
    {
        $text = 'I LOVE LARAVEL';

        $this->assertSame('i love laravel', Str::toLowerCase($text));
    }

    public function testCanUcFirstString(): void
    {
        $text = 'laravel';

        $this->assertSame('Laravel', Str::firstUpper($text));
    }

    public function testCanUcWordString(): void
    {
        $text = 'i love laravel';

        $this->assertSame('I Love Laravel', Str::firstUpperAll($text));
    }

    public function testCanSnakeCase(): void
    {
        $this->assertSame('i_love_laravel', Str::toSnakeCase('i love laravel'));
        $this->assertSame('i_love_laravel', Str::toSnakeCase('i-love-laravel'));
        $this->assertSame('i_love_laravel', Str::toSnakeCase('i_love_laravel'));
        $this->assertSame('i_love_laravel', Str::toSnakeCase('i+love+laravel'));
        $this->assertSame('i_love_laravel', Str::toSnakeCase('i+love_laravel'));
    }

    public function testCanKebabCase(): void
    {
        $this->assertSame('i-love-laravel', Str::toKebabCase('i love laravel'));
        $this->assertSame('i-love-laravel', Str::toKebabCase('i-love-laravel'));
        $this->assertSame('i-love-laravel', Str::toKebabCase('i_love_laravel'));
        $this->assertSame('i-love-laravel', Str::toKebabCase('i+love+laravel'));
        $this->assertSame('i-love-laravel', Str::toKebabCase('i+love_laravel'));
    }

    public function testCanKebabCaseFromAllUppercaseString(): void
    {
        $this->assertSame('i love laravel', Str::toKebabCase('I LOVE LARAVEL'));
    }

    public function testCanKebabCaseFromCamelCaseString(): void
    {
        $this->assertSame('i-love-laravel', Str::toKebabCase('iLoveLaravel'));
    }

    public function testCanPascalCase(): void
    {
        $this->assertSame('ILoveLaravel', Str::toPascalCase('i love laravel'));
        $this->assertSame('ILoveLaravel', Str::toPascalCase('i-love-laravel'));
        $this->assertSame('ILoveLaravel', Str::toPascalCase('i_love_laravel'));
        $this->assertSame('ILoveLaravel', Str::toPascalCase('i+love+laravel'));
        $this->assertSame('ILoveLaravel', Str::toPascalCase('i+love_laravel'));
    }

    public function testCanCamelCase(): void
    {
        $this->assertSame('iLoveLaravel', Str::toCamelCase('i love laravel'));
        $this->assertSame('iLoveLaravel', Str::toCamelCase('i-love-laravel'));
        $this->assertSame('iLoveLaravel', Str::toCamelCase('i_love_laravel'));
        $this->assertSame('iLoveLaravel', Str::toCamelCase('i+love+laravel'));
        $this->assertSame('iLoveLaravel', Str::toCamelCase('i+love_laravel'));
    }

    public function testCanCamelCaseFromSingleWord(): void
    {
        $this->assertSame('laravel', Str::toCamelCase('laravel'));
    }

    public function testCanDetectTextContainWith(): void
    {
        $text = 'i love laravel';

        $this->assertTrue(Str::contains($text, 'laravel'));
        $this->assertFalse(Str::contains($text, 'symfony'));
        $this->assertTrue(Str::contains($text, ''));
    }

    public function testCanDetectTextStartsWith(): void
    {
        $text = 'i love laravel';

        $this->assertTrue(Str::startsWith($text, 'i'));
        $this->assertFalse(Str::startsWith($text, 'love'));
    }

    public function testCanDetectTextEndsWith(): void
    {
        $text = 'i love laravel';

        $this->assertTrue(Str::endsWith($text, 'laravel'));
        $this->assertFalse(Str::endsWith($text, 'love'));
    }

    public function testCanDetectTextEndsWithEmptyString(): void
    {
        $this->assertTrue(Str::endsWith('i love laravel', ''));
        $this->assertTrue(Str::endsWith('i love laravel', 'i love laravel'));
        $this->assertTrue(Str::endsWith('i love laravel', 'laravel'));
    }

    public function testCanDetectEmptyTextEndsWith(): void
    {
        $this->assertFalse(Str::endsWith('', 'laravel'));
    }

    public function testCanDetectTextEndsWithWhenNeedleIsLongerThanText(): void
    {
        $this->assertFalse(Str::endsWith('love', 'laravel'));
    }

    public function testCanDetectTextIsMatch(): void
    {
        $this->assertTrue(Str::isMatch('i love laravel', '/love/'));
        $this->assertFalse(Str::isMatch('i love laravel', '/rust/'));
    }

    public function testCanDetectTextIsEqualToPattern(): void
    {
        $this->assertTrue(Str::is('i love laravel', '/love/'));
        $this->assertFalse(Str::is('i love laravel', '/rust/'));
    }

    public function testCanMakeSlugifyFromText(): void
    {
        $text = 'i love laravel';

        $this->assertSame('i-love-laravel', Str::slug($text));
    }

    public function testThrowsWhenSlugDoesNotReturnAnything(): void
    {
        $this->expectException(NoReturnException::class);
        $this->expectExceptionMessage('did not return anything');

        Str::slug('-~+-');
    }

    public function testCanRenderTemplateString(): void
    {
        $template = 'i love {lang}';
        $data     = ['lang' => 'laravel'];

        $this->assertSame('i love laravel', Str::template($template, $data));
    }

    public function testCanRenderTemplateStringWithCustomDelimiters(): void
    {
        $template = 'i love [[lang]]';
        $data     = ['lang' => 'laravel'];

        $this->assertSame('i love laravel', Str::template($template, $data, '[[', ']]'));
    }

    public function testCanRenderTemplateStringWithMultipleKeys(): void
    {
        $template = '{lang} is the best {framework}';
        $data     = ['lang' => 'php', 'framework' => 'laravel'];

        $this->assertSame('php is the best laravel', Str::template($template, $data));
    }

    public function testCanRenderTemplateStringWithEmptyData(): void
    {
        $template = 'i love laravel';

        $this->assertSame('i love laravel', Str::template($template, []));
    }

    public function testCanRenderTemplateStringWithCustomOpenDelimiterAndDefaultClose(): void
    {
        $template = 'i love [lang}';
        $data     = ['lang' => 'laravel'];

        $this->assertSame('i love laravel', Str::template($template, $data, '[', '}'));
    }

    public function testCanRenderTemplateStringWithDefaultOpenDelimiterAndCustomClose(): void
    {
        $template = 'i love {lang]';
        $data     = ['lang' => 'laravel'];

        $this->assertSame('i love laravel', Str::template($template, $data, '{', ']'));
    }

    public function testCanCountText(): void
    {
        $text = 'i love laravel';

        $this->assertSame(14, Str::length($text));
    }

    public function testARepeatText(): void
    {
        $text = 'Test';

        $this->assertSame('TestTestTest', Str::repeat($text, 3));
    }

    public function testCanDetectString(): void
    {
        $this->assertTrue(Str::isString('text'));
    }

    public function testCanDetectEmptyString(): void
    {
        $this->assertTrue(Str::isEmpty(''));
        $this->assertFalse(Str::isEmpty('test'));
    }

    public function testCanDetectFillStringInTheStart(): void
    {
        $this->assertSame('001212', Str::fill('1212', '0', 6));
    }

    public function testCanDetectFillStringInTheEnd(): void
    {
        $this->assertSame('121200', Str::fillEnd('1212', '0', 6));
    }

    public function testCanMakeMask(): void
    {
        $this->assertSame('l****el', Str::mask('laravel', '*', 1, 4));
        $this->assertSame('l******', Str::mask('laravel', '*', 1));
        $this->assertSame('lara*el', Str::mask('laravel', '*', -3, 1));
        $this->assertSame('lara***', Str::mask('laravel', '*', -3));
    }

    public function testCanMakeMaskFromEmptyTextWithNegativeStart(): void
    {
        $this->assertSame('', Str::mask('', '*', -1));
    }

    public function testCanMakeMaskFromEmptyTextWithPositiveStart(): void
    {
        $this->assertSame('', Str::mask('', '*', 0));
    }

    public function testCanMakeMaskFromSingleCharWhenIndexIsMasked(): void
    {
        $this->assertSame('*', Str::mask('a', '*', 0, 2));
    }

    public function testCanMakeMaskFromSingleCharWhenIndexIsBelowStart(): void
    {
        $this->assertSame('*', Str::mask('a', '*', -2, 2));
    }

    public function testCanMakeMaskFromSingleCharWhenIndexIsBeyondMaskLength(): void
    {
        $this->assertSame('a', Str::mask('a', '*', 0, 0));
    }

    public function testCanMakeMaskFromSingleCharWhenMaskIsBelowNegativeStart(): void
    {
        $this->assertSame('a', Str::mask('a', '*', -2, 0));
    }

    public function testCanMakeMaskFromSingleCharWhenStartCoversTheIndex(): void
    {
        $this->assertSame('a', Str::mask('a', '*', 1));
    }

    public function testCanMakeMaskAndContinueThroughMultipleChars(): void
    {
        $this->assertSame('*b', Str::mask('ab', '*', 0, 1));
    }

    public function testCanMakeLimit(): void
    {
        $this->assertSame('laravel best...', Str::limit('laravel best framework', 12));
    }

    public function testCanGetTextAfter(): void
    {
        $this->assertSame(
            '//localhost:8000/test',
            Str::after('https://localhost:8000/test', ':')
        );
    }

    public function testCanGetTextAfterMustReturnBack(): void
    {
        $this->assertSame(
            'https://localhost:8000/test',
            Str::after('https://localhost:8000/test', '~')
        );
    }


}