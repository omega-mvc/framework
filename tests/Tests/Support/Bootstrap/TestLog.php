<?php

/**
 * Part of Omega - Tests\Support\Bootstrap Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Support\Bootstrap;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Class TestLog
 *
 * A minimal logger used only in tests to assert that logging behavior occurs
 * as expected. Instead of writing to files or external services, this logger
 * performs PHPUnit assertions to verify:
 *
 * - The log level matches the expected value (e.g., user deprecation level).
 * - The logged message is correct.
 *
 * This ensures logging integration is functioning without introducing
 * side effects or I/O during tests.
 *
 * @category   Tests
 * @package    Support
 * @subpackage Bootstrap
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[CoversNothing]
class TestLog
{
    /**
     * Register a minimal log.
     *
     * @param int $level
     * @param string $message
     * @return void
     */
    public function log(int $level, string $message): void
    {
        Assert::assertEquals($level, 16384);
        Assert::assertEquals($message, 'deprecation');
    }
}
