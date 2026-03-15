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

namespace Tests\Support\Bootstrap\Support;

use Omega\Support\AbstractServiceProvider;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Test helper service provider for unit testing.
 *
 * This class extends AbstractServiceProvider and is designed to facilitate
 * testing the service registration process. Each call to `register()` increments
 * a static counter, allowing tests to verify that the provider was registered
 * exactly once or the expected number of times.
 *
 * It is intended solely for testing purposes and should not be used in
 * production code.
 *
 * @category   Tests
 * @package    Support
 * @subpackage Bootstrap\Support
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[CoversClass(AbstractServiceProvider::class)]
class TestRegisterProvider extends AbstractServiceProvider
{
    /**
     * Static counter tracking the number of times `register()` is called.
     *
     * Tests can inspect this property to ensure the service provider
     * registration logic is executed the expected number of times.
     *
     * @var int
     */
    public static int $called = 0;

    /**
     * Increment the static registration counter.
     *
     * This method overrides the abstract `register()` method from
     * AbstractServiceProvider. Each invocation increases the `$called`
     * property by one, enabling assertions in unit tests.
     *
     * @return void
     */
    public function register(): void
    {
        self::$called++;
    }
}
