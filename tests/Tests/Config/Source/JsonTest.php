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

#[CoversClass(MalformedJsonException::class)]
#[CoversClass(JsonConfig::class)]
class JsonTest extends TestCase
{
    /**
     * Test it should return values.
     *
     * @return void
     */
    public function testItShouldReturnValues(): void
    {
        $source = new JsonConfig(slash(path: __DIR__ . '/fixtures/content.json'));

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

        $source = new JsonConfig(slash(path: __DIR__ . '/fixtures/malformed.json'));
        $source->fetch();
    }
}
