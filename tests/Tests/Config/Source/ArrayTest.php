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

use Omega\Config\Source\ArrayConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests the ArrayConfig source, ensuring it reliably returns the array
 * provided at construction. This suite verifies that in-memory configuration
 * sources behave consistently and do not alter or transform the supplied data.
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
#[CoversClass(ArrayConfig::class)]
class ArrayTest extends TestCase
{
    /**
     * Test it should return content.
     *
     * @return void
     */
    public function testItShouldReturnContent(): void
    {
        $content = ['key' => 'value'];
        $source  = new ArrayConfig($content);

        $this->assertEquals($content, $source->fetch());
    }
}
