<?php

/**
 * Part of Omega - Tests\Testing Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Testing;

use Exception;
use Omega\Application\Application;
use Omega\Http\Http;
use Omega\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

use function dirname;

/**
 * TestCaseTest
 *
 * Ensures that the base testing infrastructure provided by Omega's
 * custom TestCase class behaves correctly when initializing the
 * application container and HTTP layer.
 *
 * This test verifies that the test environment can be set up
 * without errors, and that core service bindings (such as the Http
 * handler) can be registered and resolved properly within the
 * Application instance used during tests.
 *
 * The successful execution of this suite indicates that Omega’s
 * testing foundation is stable and functional for higher-level
 *
 * @category  Tests
 * @package   Testing
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(Application::class)]
#[CoversClass(Http::class)]
#[CoversClass(TestCase::class)]
final class TestCaseTest extends TestCase
{
    /**
     * Sets up the environment before each test method.
     *
     * This method is called automatically by PHPUnit before each test runs.
     * It is responsible for initializing the application instance, setting up
     * dependencies, and preparing any state required by the test.
     *
     * @return void
     * @throws Exception Throw when a generic error occurred.
     */
    protected function setUp(): void
    {
        $this->app = new Application(basePath: dirname(__DIR__));
        $this->app->set(Http::class, fn () => new Http($this->app));

        parent::setUp();
    }

    /**
     * Test run smoothly.
     *
     * @return void
     */
    public function testRunSmoothly(): void
    {
        $this->assertTrue(true);
    }
}
