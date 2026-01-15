<?php

/**
 * Part of Omega - Tests\Time Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Time;

use DateInvalidTimeZoneException;
use DateMalformedStringException;
use Omega\Time\Now;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function Omega\Time\now;
use function time;

/**
 * Test suite for helper functions provided by the date/time component.
 *
 * This class verifies that the global `now()` helper returns a valid `Now`
 * instance and behaves consistently with the expected timestamp output.
 *
 * @category  Tests
 * @package   Time
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(Now::class)]
final class HelperFunctionTest extends TestCase
{
    /**
     * Tests the global `now()` helper function.
     *
     * Ensures that calling the `now()` function returns a valid `Now` instance
     * whose timestamp corresponds to the current system time.
     *
     * @return void
     * @throws DateInvalidTimeZoneException Thrown when a provided timezone is invalid.
     * @throws DateMalformedStringException Thrown when a date string cannot be parsed correctly.
     */
    public function testItCanUseFunctionHelper(): void
    {
        $this->assertEquals(time(), now()->timestamp);
    }
}
