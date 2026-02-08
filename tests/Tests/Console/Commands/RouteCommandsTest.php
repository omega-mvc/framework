<?php

/**
 * Part of Omega - Tests\Console Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Console\Commands\RouteCommand;
use Omega\Router\Router;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Test suite for the Route console command.
 *
 * This class verifies the behavior of the `RouteCommand`, which is responsible
 * for rendering and displaying the application's registered routes in a
 * console-friendly format. The tests ensure that routes of different HTTP
 * methods (e.g., GET, POST) are correctly listed, and that the command output
 * reflects the expected route definitions.
 *
 * Each test runs in isolation, defining temporary routes using the `Router`
 * class and capturing command output via output buffering. The `Router` is
 * reset after each test to prevent side effects and ensure test independence.
 *
 * This suite guarantees that the route listing command accurately represents
 * the application's routing configuration and integrates correctly with the
 * routing system.
 *
 * @category   Tests
 * @package    Console
 * @subpackage Commands
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html GPL V3.0+
 * @version    2.0.0
 */
#[CoversClass(RouteCommand::class)]
#[CoversClass(Router::class)]
final class RouteCommandsTest extends AbstractTestCommand
{
    /**
     * Test it can render route with some router.
     *
     * @return void
     */
    public function testItCanRenderRouteWithSomeRouter(): void
    {
        Router::get('/test', fn () => '');
        Router::post('/post', fn () => '');

        $route_command = new RouteCommand($this->argv('php route:list'));
        ob_start();
        $exit = $route_command->main();
        $out  = ob_get_clean();

        $this->assertSuccess($exit);
        $this->assertContain('GET', $out);
        $this->assertContain('/test', $out);

        Router::Reset();
    }
}
