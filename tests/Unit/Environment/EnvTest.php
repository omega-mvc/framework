<?php

declare(strict_types=1);

namespace Tests\Environment;

use Omega\Environment\Env;
use ReflectionClass;
use function putenv;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class EnvTest extends TestCase
{
    protected function setUp(): void
    {
        $this->fixturePath = __DIR__ . '/fixtures/support/';
    }

    protected function tearDown(): void
    {
        $valuesProp = (new ReflectionClass(Env::class))->getProperty('values');
        $valuesProp->setValue(null, []);
    }

#[Test]

public function test_can_create_immutable_dotenv_instance_and_load_values(): void {
    Env::load($this->fixturePath, '.env.test');

    $this->assertSame('Omega', Env::get('APP_NAME'));
    }


#[Test]

public function test_returns_default_value_when_key_not_found(): void {
    $default = 'default_value';

    $this->assertSame($default, Env::get('NON_EXISTING_KEY', $default));
    }





#[Test]

public function test_returns_non_string_values_as_is(): void {
    $valuesProp = (new ReflectionClass(Env::class))->getProperty('values');
    $valuesProp->setValue(null, [
        'ARRAY_VAL' => [1, 2, 3],
        'INT_VAL'   => 100,
        'BOOL_VAL'  => false,
        'NULL_VAL'  => null,
    ]);

    $this->assertEquals([1, 2, 3], Env::get('ARRAY_VAL'));
    $this->assertSame(100, Env::get('INT_VAL'));
    $this->assertFalse(Env::get('BOOL_VAL'));
    $this->assertNull(Env::get('NULL_VAL'));
    }


#[Test]

public function test_falls_back_to_getenv(): void {
    putenv('SYSTEM_VAR=hello');
    $this->assertSame('hello', Env::get('SYSTEM_VAR'));
    putenv('SYSTEM_VAR');
    }

    #[DataProvider('typeConversionProvider')]
    public function test_converts_string_representations_to_native_types(string $key, mixed $rawValue, mixed $expected): void
    {
        $valuesProp = (new ReflectionClass(Env::class))->getProperty('values');
        $valuesProp->setValue(null, [$key => $rawValue]);

        $this->assertSame($expected, Env::get($key));
    }

    public static function typeConversionProvider(): array
    {
        return [
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
        ];
    }

}