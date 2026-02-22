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

use Omega\Application\Application;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class TestBootstrapProvider
 *
 * A simple support class used in testing the Application.
 *
 * This provider is responsible for bootstrapping the Application instance
 * during tests. Its primary purpose is to verify that the bootstrap process
 * is invoked correctly without performing any real initialization logic.
 *
 * Usage in tests:
 * - Injected into the Application to simulate a bootstrap provider.
 * - Outputs the class and method name to confirm execution.
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
#[CoversClass(Application::class)]
class TestBootstrapProvider
{
    public function bootstrap(Application $app): void
    {
        echo __CLASS__ . '::' . __FUNCTION__;
    }
}
