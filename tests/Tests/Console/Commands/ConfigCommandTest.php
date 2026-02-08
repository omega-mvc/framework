<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Omega\Application\Application;
use Omega\Console\Commands\ConfigCommand;
use Tests\FixturesPathTrait;

use function file_exists;
use function ob_get_clean;
use function ob_start;
use function unlink;

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
     * @throws Exception
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
     * @throws Exception
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
