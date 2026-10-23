<?php

declare(strict_types=1);

namespace Tests\Application;

use InvalidArgumentException;
use Omega\Application\Application;
use Omega\Exceptions\ApplicationNotAvailableException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use Tests\TestCase;

use function Omega\Application\app;
use function Omega\Application\get_path;
use function Omega\Application\is_dev;
use function Omega\Application\is_production;
use function Omega\Application\os_detect;
use function Omega\Application\path;
use function Omega\Application\set_path;
use function Omega\Application\slash;

#[CoversClass(Application::class)]
#[CoversFunction('Omega\Application\app')]
#[CoversFunction('Omega\Application\get_path')]
#[CoversFunction('Omega\Application\is_dev')]
#[CoversFunction('Omega\Application\is_production')]
#[CoversFunction('Omega\Application\os_detect')]
#[CoversFunction('Omega\Application\path')]
#[CoversFunction('Omega\Application\set_path')]
#[CoversFunction('Omega\Application\slash')]
final class HelperTest extends TestCase
{
    public function testThrowsErrorAfterFlushingTheApplication(): void
    {
        $app = new Application('/');
        $app->flush();

        $this->expectException(ApplicationNotAvailableException::class);

        app();
    }

    public function testLoadsTheApplication(): void
    {
        $app = new Application('');

        $this->assertSame('/', app()->get('path.base'));

        $app->flush();
    }

    public function testResolvesEnvironmentHelpers(): void
    {
        $app = new Application(__DIR__);

        $app->set('environment', 'prod');
        $this->assertFalse(is_dev());
        $this->assertTrue(is_production());
    }

    public function testDetectsAllSupportedOsFamilies(): void
    {
        $this->assertSame('windows', os_detect('Windows'));
        $this->assertSame('linux', os_detect('Linux'));
        $this->assertSame('mac', os_detect('Darwin'));
        $this->assertSame('bsd', os_detect('Bsd'));
        $this->assertSame('solaris', os_detect('Solaris'));
        $this->assertSame('unknown', os_detect('AmigaOS'));

        $currentOs = strtolower(PHP_OS_FAMILY);
        $expected  = match ($currentOs) {
            'darwin' => 'mac',
            'windows', 'linux', 'bsd', 'solaris' => $currentOs,
            default => 'unknown',
        };

        $this->assertSame($expected, os_detect());
    }

    public function testHandlesBothStringsAndArraysInSlash(): void
    {
        $separator = DIRECTORY_SEPARATOR;

        $this->assertSame("a{$separator}b", slash('a/b'));
        $this->assertSame('c', slash('c'));

        $input    = ['a/b', 'c/d'];
        $expected = ["a{$separator}b", "c{$separator}d"];

        $this->assertSame($expected, slash($input));
    }

    public function testNormalizesPaths(): void
    {
        $ds = DIRECTORY_SEPARATOR;

        $this->assertSame("app{$ds}config{$ds}", path('app.config'));
        $this->assertSame("app{$ds}logs{$ds}", path("app.logs{$ds}"));

        $input    = ['core.view', 'cache'];
        $expected = ["core{$ds}view{$ds}", "cache{$ds}"];
        $this->assertSame($expected, path($input));

        $this->assertSame("{$ds}", path(''));
    }

    public function testGetsPathWithArrayAndSuffix(): void
    {
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

        $this->assertSame($expected, $result);
    }

    public function testGetsPathWithStringAndSuffix(): void
    {
        $app = new Application(__DIR__);
        $ds  = DIRECTORY_SEPARATOR;

        $app->set('single_path', 'app/core/');

        $result = get_path('single_path', 'test/');

        $this->assertSame("app/core/test{$ds}", $result);
    }

    public function testGetsPathWithArrayOfStringIdentifiers(): void
    {
        $app = new Application(__DIR__);
        $ds  = DIRECTORY_SEPARATOR;

        $app->set('a_path', 'a/');
        $app->set('b_path', 'b/');

        $result = get_path(['a_path', 'b_path'], 'x/');

        $this->assertSame(["a/x{$ds}", "b/x{$ds}"], $result);
    }

    public function testGetsPathWithNonStringBindingValue(): void
    {
        $app = new Application(__DIR__);
        $ds  = DIRECTORY_SEPARATOR;

        $app->set('int_path', 42);

        $result = get_path('int_path', 'suf/');

        $this->assertSame("suf{$ds}", $result);
    }

    public function testGetsPathWithNonStringElementInsideArrayValue(): void
    {
        $app = new Application(__DIR__);
        $ds  = DIRECTORY_SEPARATOR;

        $app->set('mixed_path', ['str' => 'app/', 'num' => 42]);

        $result = get_path('mixed_path', 'suf/');

        $this->assertSame(['str' => "app/suf{$ds}", 'num' => "suf{$ds}"], $result);
    }

    public function testGetsPathWithArrayIdentifierContainingArrayValue(): void
    {
        $app = new Application(__DIR__);

        $app->set('mixed_path', ['str' => 'app/', 'num' => 42]);

        $result = get_path(['mixed_path'], 'suf/');

        $this->assertSame([''], $result);
    }

    public function testSetsPathWithSingleString(): void
    {
        $ds      = DIRECTORY_SEPARATOR;
        $input   = 'app.config.storage';
        $expected = "{$ds}app{$ds}config{$ds}storage{$ds}";

        $this->assertSame($expected, set_path($input));
    }

    public function testSetsPathWithArrayOfStrings(): void
    {
        $ds       = DIRECTORY_SEPARATOR;
        $input    = ['app.config', 'public.assets'];
        $expected = [
            "{$ds}app{$ds}config{$ds}",
            "{$ds}public{$ds}assets{$ds}",
        ];

        $this->assertSame($expected, set_path($input));
    }

    public function testThrowsExceptionOnEmptyStringPath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The path key cannot be an empty string');

        set_path('');
    }

    public function testThrowsExceptionOnEmptyArrayPath(): void
    {
        $this->expectException(InvalidArgumentException::class);

        set_path([]);
    }
}
