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

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Omega\Application\Application;
use Omega\Console\Commands\ConfigCommand;
use Psr\Container\ContainerExceptionInterface;
use Tests\FixturesPathTrait;

use function file_exists;
use function ob_get_clean;
use function ob_start;
use function unlink;

/**
 * Test suite for the Config console command.
 *
 * This class tests the behavior of the `config:cache` and `config:clear`
 * console command actions. It verifies that configuration files can be
 * correctly generated and written to disk, and that existing cached
 * configuration files can be safely removed.
 *
 * The tests operate on dedicated filesystem fixtures that simulate both
 * writable and read-only application environments. This allows the command
 * to be validated against real path resolution, file creation, and cleanup
 * logic without affecting the actual application state.
 *
 * Each test ensures proper isolation by removing the generated cache file
 * during teardown, guaranteeing a clean filesystem state for subsequent
 * test executions.
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
#[CoversClass(Application::class)]
#[CoversClass(ConfigCommand::class)]
class ConfigCommandTest extends TestCase
{
    use FixturesPathTrait;

    /**
     * Tears down the environment after each test method.
     *
     * This method is called automatically by PHPUnit after each test runs.
     * It is responsible for cleaning up resources, flushing the application
     * state, unsetting properties, and resetting any static or global state
     * to avoid side effects between tests.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        if (file_exists($file = $this->setFixturePath('/fixtures/application-write/bootstrap/cache/cache.php'))) {
            @unlink($file);
        }
    }

    /**
     * Test it can create config file.
     *
     * @return void
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws Exception Throw when a generic error occurred.
     */
    public function testItCanCreateConfigFile(): void
    {
        $app = new Application($this->setFixturePath('/fixtures/application-write/'));
        $app->set('path.config', $this->setFixturePath(slash(path: '/fixtures/application-write/config/')));

        $command = new ConfigCommand([]);

        ob_start();
        $status = $command->main();
        $out    = ob_get_clean();

        $this->assertEquals(0, $status);
        $this->assertStringContainsString('Configuration cached successfully.', $out);

        $app->flush();
    }

    /**
     * Test it can remove config file.
     *
     * @return void
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws Exception Throw when a generic error occurred.
     */
    public function testItCanRemoveConfigFile(): void
    {
        $app = new Application($this->setFixturePath(slash(path: '/fixtures/application-write/')));
        $app->set('path.config', $this->setFixturePath(slash(path: '/fixtures/application-read/config/')));

        $command = new ConfigCommand([]);

        ob_start();
        $command->main();
        $status = $command->clear();
        $out    = ob_get_clean();

        $this->assertEquals(0, $status);
        $this->assertStringContainsString('Configuration cache cleared successfully.', $out);

        $app->flush();
    }
}
