<?php

declare(strict_types=1);

namespace Tests\Text;

use Omega\Text\Regex;
use Omega\Text\Str;

use function expect;

covers(Regex::class);
covers(Str::class);

it('regex email', function (): void {
    $res = Str::isMatch('agisoftt@mail.com', Regex::EMAIL);
    expect($res)->toBeTrue();

    $res = Str::isMatch('agisoftt.com', Regex::EMAIL);
    expect($res)->toBeFalse();
});

it('regex username', function (): void {
    $res = Str::isMatch('agisoftt', Regex::USER);
    expect($res)->toBeTrue();

    $res = Str::isMatch('1agisoftt', Regex::USER);
    expect($res)->toBeFalse();

    $res = Str::isMatch('agi', Regex::USER);
    expect($res)->toBeFalse();

    $res = Str::isMatch('test_regex_username', Regex::USER);
    expect($res)->toBeFalse();
});

it('regex plain text', function (): void {
    $res = Str::isMatch('php generators explained', Regex::PLAIN_TEXT);
    expect($res)->toBeTrue();

    $res = Str::isMatch('php generators explained!', Regex::PLAIN_TEXT);
    expect($res)->toBeFalse();
});

it('regex slug', function (): void {
    $res = Str::isMatch('php-generators-explained', Regex::SLUG);
    expect($res)->toBeTrue();

    $res = Str::isMatch('php generators explained', Regex::SLUG);
    expect($res)->toBeFalse();

    $res = Str::isMatch('php/generators/explained', Regex::SLUG);
    expect($res)->toBeFalse();
});

it('regex html tag', function (): void {
    $res = Str::isMatch('<script>alert(1)</alert>', Regex::HTML_TAG);
    expect($res)->toBeTrue();

    $res = Str::isMatch('&lt;script&gt;alert(1)&lt;/alert&gt;', Regex::HTML_TAG);
    expect($res)->toBeFalse();
});

it('regex js in line', function (): void {
    $res = Str::isMatch('<img src="foo.jpg" onload=function_xyz />', Regex::JS_INLINE);
    expect($res)->toBeTrue();
});

it('regex password', function (): void {
    $res = Str::isMatch('Password123@', Regex::PASSWORD_COMPLEX);
    expect($res)->toBeTrue();

    $res = Str::isMatch('Password123', Regex::PASSWORD_COMPLEX);
    expect($res)->toBeFalse();
});

it('regex password moderate', function (): void {
    $res = Str::isMatch('Password123', Regex::PASSWORD_MODERATE);
    expect($res)->toBeTrue();

    $res = Str::isMatch('password123', Regex::PASSWORD_MODERATE);
    expect($res)->toBeFalse();

    $res = Str::isMatch('Passwordddd', Regex::PASSWORD_MODERATE);
    expect($res)->toBeFalse();

    $res = Str::isMatch('Pwd123', Regex::PASSWORD_MODERATE);
    expect($res)->toBeFalse();
});

it('regex date year month day', function (): void {
    $res = Str::isMatch('2022-12-31', Regex::DATE_YYYYMMDD);
    expect($res)->toBeTrue();

    $res = Str::isMatch('2022-31-12', Regex::DATE_YYYYMMDD);
    expect($res)->toBeFalse();
});

it('regex date day month year', function (): void {
    $res = Str::isMatch('31-12-2022', Regex::DATE_DDMMYYYY);
    expect($res)->toBeTrue();

    $res = Str::isMatch('12-31-2022', Regex::DATE_DDMMYYYY);
    expect($res)->toBeFalse();

    $res = Str::isMatch('31.12.2022', Regex::DATE_DDMMYYYY);
    expect($res)->toBeTrue();

    $res = Str::isMatch('12.31.2022', Regex::DATE_DDMMYYYY);
    expect($res)->toBeFalse();

    $res = Str::isMatch('31/12/2022', Regex::DATE_DDMMYYYY);
    expect($res)->toBeTrue();

    $res = Str::isMatch('12/31/2022', Regex::DATE_DDMMYYYY);
    expect($res)->toBeFalse();
});

it('regex date day month name year', function (): void {
    $res = Str::isMatch('01-Jun-2022', Regex::DATE_DDMMMYYYY);
    expect($res)->toBeTrue();

    $res = Str::isMatch('Jun-01-2022', Regex::DATE_DDMMMYYYY);
    expect($res)->toBeFalse();

    $res = Str::isMatch('01/Jun/2022', Regex::DATE_DDMMMYYYY);
    expect($res)->toBeTrue();

    $res = Str::isMatch('Jun/01/2022', Regex::DATE_DDMMMYYYY);
    expect($res)->toBeFalse();

    $res = Str::isMatch('01.Jun.2022', Regex::DATE_DDMMMYYYY);
    expect($res)->toBeTrue();

    $res = Str::isMatch('Jun.01.2022', Regex::DATE_DDMMMYYYY);
    expect($res)->toBeFalse();
});

it('regex ipv4', function (): void {
    $test = '0.0.0.0';
    expect(Str::isMatch($test, Regex::IPV4))->toBeTrue();
});

it('regex ipv6', function (): void {
    $test = '1200:0000:AB00:1234:0000:2552:7777:1313';
    expect(Str::isMatch($test, Regex::IPV6))->toBeTrue();

    $test = '1200:0000:AB00:1234:O000:2552:7777:1313';
    expect(Str::isMatch($test, Regex::IPV6))->toBeFalse();
});

it('regex ipv4 or ipv6', function (): void {
    $test = '0.0.0.0';
    expect(Str::isMatch($test, Regex::IPV4_6))->toBeTrue();

    $test = '1200:0000:AB00:1234:0000:2552:7777:1313';
    expect(Str::isMatch($test, Regex::IPV4_6))->toBeTrue();

    $test = '1200:0000:AB00:1234:O000:2552:7777:1313';
    expect(Str::isMatch($test, Regex::IPV4_6))->toBeFalse();
});

it('regex url', function (): void {
    $test = 'https://stackoverflow.com/questions/206059/php-validation-regex-for-url';
    expect(Str::isMatch($test, Regex::URL))->toBeTrue();

    $test = 'http://stackoverflow.com/questions/206059/php-validation-regex-for-url';
    expect(Str::isMatch($test, Regex::URL))->toBeTrue();
});