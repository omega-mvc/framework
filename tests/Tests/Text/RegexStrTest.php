<?php

/**
 * Part of Omega - Tests\Text Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Text;

use Omega\Text\Regex;
use Omega\Text\Str;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Test suite for validating predefined regular expressions.
 *
 * This class verifies that all built-in Regex patterns correctly match
 * or reject common string formats such as emails, usernames, plain text,
 * slugs, HTML tags, inline JavaScript, passwords, dates, IP addresses,
 * and URLs. Each test ensures the `Str::isMatch()` method behaves as
 * expected for both valid and invalid inputs.
 *
 * @category  Tests
 * @package   Text
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(Regex::class)]
#[CoversClass(Str::class)]
final class RegexStrTest extends TestCase
{
    /**
     * Test regex email.
     *
     * @return void
     */
    public function testRegexEmail(): void
    {
        $res = Str::isMatch('agisoftt@mail.com', Regex::EMAIL);
        $this->assertTrue($res);

        $res = Str::isMatch('agisoftt.com', Regex::EMAIL);
        $this->assertFalse($res);
    }

    /**
     * Text regex username.
     *
     * @return void
     */
    public function testRegexUsername(): void
    {
        $res = Str::isMatch('agisoftt', Regex::USER);
        $this->assertTrue($res);

        $res = Str::isMatch('1agisoftt', Regex::USER);
        $this->assertFalse($res);

        $res = Str::isMatch('agi', Regex::USER);
        $this->assertFalse($res);

        $res = Str::isMatch('test_regex_username', Regex::USER);
        $this->assertFalse($res);
    }

    /**
     * Test regex plain text.
     *
     * @return void
     */
    public function testRegexPlainText(): void
    {
        $res = Str::isMatch('php generators explained', Regex::PLAIN_TEXT);
        $this->assertTrue($res);

        $res = Str::isMatch('php generators explained!', Regex::PLAIN_TEXT);
        $this->assertFalse($res);
    }

    /**
     * Test regex slug.
     *
     * @return void
     */
    public function testRegexSlug(): void
    {
        $res = Str::isMatch('php-generators-explained', Regex::SLUG);
        $this->assertTrue($res);

        $res = Str::isMatch('php generators explained', Regex::SLUG);
        $this->assertFalse($res);

        $res = Str::isMatch('php/generators/explained', Regex::SLUG);
        $this->assertFalse($res);
    }

    /**
     * Test regex html tag.
     *
     * @return void
     */
    public function testRegexHtmlTag(): void
    {
        $res = Str::isMatch('<script>alert(1)</alert>', Regex::HTML_TAG);
        $this->assertTrue($res);

        $res = Str::isMatch('&lt;script&gt;alert(1)&lt;/alert&gt;', Regex::HTML_TAG);
        $this->assertFalse($res);
    }

    /**
     * test regex js in line.
     *
     * @return void
     */
    public function testRegexJsInline(): void
    {
        $res = Str::isMatch('<img src="foo.jpg" onload=function_xyz />', Regex::JS_INLINE);
        $this->assertTrue($res);
    }

    /**
     * test regex password.
     *
     * @return void
     */
    public function testRegexPassword(): void
    {
        $res = Str::isMatch('Password123@', Regex::PASSWORD_COMPLEX);
        $this->assertTrue($res);

        $res = Str::isMatch('Password123', Regex::PASSWORD_COMPLEX);
        $this->assertFalse($res);
    }

    /**
     * Test regex password moderate.
     *
     * @return void
     */
    public function testRegexPasswordModerate(): void
    {
        $res = Str::isMatch('Password123', Regex::PASSWORD_MODERATE);
        $this->assertTrue($res);

        $res = Str::isMatch('password123', Regex::PASSWORD_MODERATE);
        $this->assertFalse($res);

        $res = Str::isMatch('Passwordddd', Regex::PASSWORD_MODERATE);
        $this->assertFalse($res);

        $res = Str::isMatch('Pwd123', Regex::PASSWORD_MODERATE);
        $this->assertFalse($res);
    }

    /**
     * Test regex date year month day.
     *
     * @return void
     */
    public function testRegexDateYearMonthDay(): void
    {
        $res = Str::isMatch('2022-12-31', Regex::DATE_YYYYMMDD);
        $this->assertTrue($res);

        $res = Str::isMatch('2022-31-12', Regex::DATE_YYYYMMDD);
        $this->assertFalse($res);
    }

    /**
     * Test regex date day month year.
     *
     * @return void
     */
    public function testRegexDateDayMonthYear(): void
    {
        $res = Str::isMatch('31-12-2022', Regex::DATE_DDMMYYYY);
        $this->assertTrue($res);

        $res = Str::isMatch('12-31-2022', Regex::DATE_DDMMYYYY);
        $this->assertFalse($res);

        $res = Str::isMatch('31.12.2022', Regex::DATE_DDMMYYYY);
        $this->assertTrue($res);

        $res = Str::isMatch('12.31.2022', Regex::DATE_DDMMYYYY);
        $this->assertFalse($res);

        $res = Str::isMatch('31/12/2022', Regex::DATE_DDMMYYYY);
        $this->assertTrue($res);

        $res = Str::isMatch('12/31/2022', Regex::DATE_DDMMYYYY);
        $this->assertFalse($res);
    }

    /**
     * Test regex date day month name year.
     *
     * @return void
     */
    public function testRegexDateDayMonthNameYear(): void
    {
        $res = Str::isMatch('01-Jun-2022', Regex::DATE_DDMMMYYYY);
        $this->assertTrue($res);

        $res = Str::isMatch('Jun-01-2022', Regex::DATE_DDMMMYYYY);
        $this->assertFalse($res);

        $res = Str::isMatch('01/Jun/2022', Regex::DATE_DDMMMYYYY);
        $this->assertTrue($res);

        $res = Str::isMatch('Jun/01/2022', Regex::DATE_DDMMMYYYY);
        $this->assertFalse($res);

        $res = Str::isMatch('01.Jun.2022', Regex::DATE_DDMMMYYYY);
        $this->assertTrue($res);

        $res = Str::isMatch('Jun.01.2022', Regex::DATE_DDMMMYYYY);
        $this->assertFalse($res);
    }

    /**
     * Test regex ipv4.
     *
     * @return void
     */
    public function testRegexIpv4(): void
    {
        $test = '0.0.0.0';
        $this->assertTrue(Str::isMatch($test, Regex::IPV4));
    }

    /**
     * Text regex ipv6.
     *
     * @return void
     */
    public function testRegexIpv6(): void
    {
        $test = '1200:0000:AB00:1234:0000:2552:7777:1313';
        $this->assertTrue(Str::isMatch($test, Regex::IPV6));

        $test = '1200:0000:AB00:1234:O000:2552:7777:1313';
        $this->assertFalse(Str::isMatch($test, Regex::IPV6));
    }

    /**
     * Text regex ipv4 or ipv6.
     *
     * @return void
     */
    public function testRegexIpv4OrIpv6(): void
    {
        $test = '0.0.0.0';
        $this->assertTrue(Str::isMatch($test, Regex::IPV4_6));

        $test = '1200:0000:AB00:1234:0000:2552:7777:1313';
        $this->assertTrue(Str::isMatch($test, Regex::IPV4_6));

        $test = '1200:0000:AB00:1234:O000:2552:7777:1313';
        $this->assertFalse(Str::isMatch($test, Regex::IPV4_6));
    }

    /**
     * Text regex url.
     *
     * @return void
     */
    public function testRegexUrl(): void
    {
        $test = 'https://stackoverflow.com/questions/206059/php-validation-regex-for-url';
        $this->assertTrue(Str::isMatch($test, Regex::URL));

        $test = 'http://stackoverflow.com/questions/206059/php-validation-regex-for-url';
        $this->assertTrue(Str::isMatch($test, Regex::URL));
    }
}
