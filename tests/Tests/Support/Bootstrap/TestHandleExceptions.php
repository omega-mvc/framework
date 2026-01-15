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

use Omega\Exceptions\ExceptionHandler;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use Throwable;

/**
 * Class TestHandleExceptions
 *
 * A custom exception handler used exclusively for testing the exception
 * handling bootstrap process. Instead of logging or displaying errors,
 * this implementation asserts expected behavior, ensuring that exceptions
 * are correctly passed through the handler during tests.
 *
 * - `report()` asserts the received exception message.
 * - `deprecated()` is used to verify the deprecation handling workflow.
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
#[CoversClass(ExceptionHandler::class)]
class TestHandleExceptions extends ExceptionHandler
{
    /**
     * Report exception.
     *
     * @param Throwable $th
     * @return void
     */
    public function report(Throwable $th): void
    {
        Assert::assertTrue($th->getMessage() === 'testing', 'testing helper');
    }

    /**
     * Summary of deprecated.
     *
     * @deprecated message
     * @return void
     */
    public function deprecated(): void
    {
    }
}
