<?php

/**
 * Part of Omega - Tests\Logging Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Logging\Exception;

use InvalidArgumentException;
use Omega\Logging\Exception\LogArgumentException;
use Omega\Logging\Exception\UnknownDriverException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Class LoggingExceptionTest
 *
 * This test suite verifies the behavior of the exception classes provided
 * by the Logging package.
 *
 * @category   Tests
 * @package    Logging
 * @subpackage Exception
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[CoversClass(LogArgumentException::class)]
#[CoversClass(UnknownDriverException::class)]
final class LoggingExceptionTest extends TestCase
{
    /**
     * Test LogArgumentException.
     *
     * @return void
     */
    public function testLogArgumentException(): void
    {
        $exception = new LogArgumentException('invalid log');

        $this->assertInstanceOf(InvalidArgumentException::class, $exception);
        $this->assertSame('invalid log', $exception->getMessage());
    }

    /**
     * Test UnknownDriverException.
     *
     * @return void
     */
    public function testUnknownDriverException(): void
    {
        $exception = new UnknownDriverException('stream');

        $this->assertSame(
            'The log driver "stream" could not be resolved or is not registered.',
            $exception->getMessage()
        );
    }
}
