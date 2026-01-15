<?php

/**
 * Part of Omega - Tests\Collection Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Collection;

use Omega\Collection\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function Omega\Collection\data_get;

/**
 * Class DataGetTest
 *
 * This test class validates the behavior of the `data_get` helper function.
 * It ensures that values can be retrieved from nested arrays using dot notation,
 * supporting:
 * - Nested keys of arbitrary depth.
 * - Default values when keys do not exist.
 * - Wildcard segments (*) for arrays of arrays.
 * - Integer keys for indexed arrays.
 *
 * @category  Tests
 * @package   Collection
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(Collection::class)]
class DataGetTest extends TestCase
{
    /**
     * Sample nested array for testing `data_get`.
     *
     * Structure includes:
     * - Multiple levels of nesting.
     * - Arrays with numeric and string keys.
     * - Wildcard scenarios.
     *
     * @var array<string, mixed>
     */
    private array $array = [
        'awesome'   => [
            'lang' => [
                'go',
                'rust',
                'php',
                'python',
                'js',
            ],
        ],
        'fav'       => [
            'lang' => [
                'rust',
                'php',
            ],
        ],
        'dont_know' => ['lang_' => ['back_end' => ['erlang', 'h-lang']]],
        'one'       => ['two' => ['three' => ['four' => ['five' => 6]]]],
    ];

    /**
     * Test it can find item using dot keys.
     *
     * @return void
     */
    public function testItCanFindItemUsingDotKeys(): void
    {
        $this->assertEquals(6, data_get($this->array, 'one.two.three.four.five'));
    }

    /**
     * Test it can find item using dot keys but key does not exist.
     *
     * @return void
     */
    public function testItCanFindItemUsingDotKeysButDontExist(): void
    {
        $this->assertEquals('six', data_get($this->array, '1.2.3.4.5', 'six'));
    }

    /**
     * Test it can find items using dot key with wildcard (*).
     *
     * @return void
     */
    public function testItCanFindItemUsingDotKeysWithWildcard(): void
    {
        $this->assertEquals([
            ['go', 'rust', 'php', 'python', 'js'],
            ['rust', 'php'],
        ], data_get($this->array, '*.lang'));
    }

    /**
     * Test it can retrieve values using integer keys for indexed arrays.
     *
     * @return void
     */
    public function testItCanGetKeysAsInteger(): void
    {
        $array5 = ['foo', 'bar', 'baz'];
        $this->assertEquals('bar', data_get($array5, 1));
        $this->assertNull(data_get($array5, 3));
        $this->assertEquals('qux', data_get($array5, 3, 'qux'));
    }
}
