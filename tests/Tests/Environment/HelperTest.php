<?php

/**
 * Part of Omega - Tests\Environment Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Environment;

use Omega\Application\Application;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;

use function Omega\Environment\env;

/**
 * Test suite for Omega global helper functions.
 *
 * This class verifies the behavior and consistency of all core helper
 * functions provided by the Omega\Application namespace.
 *
 * The tests cover:
 * - Environment helpers such as `env()`, `is_dev()`, and `is_production()`.
 *
 * This suite ensures that helper functions behave consistently across
 * different input types (scalar vs array), maintain cross-platform
 * compatibility, and correctly integrate with the underlying application
 * infrastructure.
 *
 * @category  Tests
 * @package   Environment
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(Application::class)]
#[CoversFunction('Omega\Environment\env')]
final class HelperTest extends TestCase
{
    /**
     * Test env helper returns value if not exists.
     *
     * @return void
     */
    public function testEnvHelperReturnsValueIfNotExists(): void
    {
        $default = 'default_value';

        $this->assertSame($default, env('NON_EXISTING_KEY', $default));
    }
}
