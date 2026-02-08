<?php

/**
 * Part of Omega - Tests\Config Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Config\Source;

use Omega\Config\Exceptions\MalformedJsonException;
use Omega\Config\Source\JsonConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\FixturesPathTrait;

/**
 * Test suite for the JsonConfig source.
 *
 * It verifies that JSON configuration files are correctly decoded into
 * associative arrays and that malformed JSON input results in the appropriate
 * exception being thrown. The tests cover both valid and invalid scenarios
 * to ensure robustness of the JSON configuration loader.
 *
 * @category   Tests
 * @package    Config
 * @subpackage Source
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[CoversClass(MalformedJsonException::class)]
#[CoversClass(JsonConfig::class)]
class JsonTest extends TestCase
{
    use FixturesPathTrait;

    /**
     * Test it should return values.
     *
     * @return void
     */
    public function testItShouldReturnValues(): void
    {
        $source = new JsonConfig($this->setFixturePath(slash(path: '/fixtures/config/content.json')));

        $this->assertEquals(['key' => 'value'], $source->fetch());
    }

    /**
     * Test it should throw on malformed configuration.
     *
     * @return void
     */
    public function testItShouldThrowOnMalformedConfiguration(): void
    {
        $this->expectException(MalformedJsonException::class);

        $source = new JsonConfig($this->setFixturePath(slash(path: '/fixtures/config/malformed.json')));

        $source->fetch();
    }
}
