<?php

/**
 * Part of Omega - Tests\Config Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Config\Facade;

use Omega\Application\Application;
use Omega\Config\ConfigSource as ConfigSourceService;
use Omega\Config\Facade\ConfigSource;
use Omega\Facade\AbstractFacade;
use Omega\Facade\Exceptions\FacadeObjectNotSetException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\FixturesPathTrait;

/**
 * Class ConfigSourceFacadeTest
 *
 * This test suite verifies the behavior of the {@see ConfigSource} facade: its
 * container accessor and the static forwarding of calls — including statically
 * registered macros — onto the underlying macroable {@see ConfigSourceService}.
 *
 * @category   Tests
 * @package    Config
 * @subpackage Facade
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[CoversClass(ConfigSource::class)]
#[CoversClass(ConfigSourceService::class)]
final class ConfigSourceFacadeTest extends TestCase
{
    use FixturesPathTrait;

    /** @var Application|null The application created during a test. */
    private ?Application $app = null;

    /**
     * Sets up the environment before each test method.
     *
     * @return void
     */
    protected function setUp(): void
    {
        ConfigSourceService::resetMacro();
    }

    /**
     * Tears down the environment after each test method.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        AbstractFacade::setFacadeBase(null);
        AbstractFacade::flushInstance();

        $this->app?->flush();
    }

    /**
     * Test the facade accessor.
     *
     * @return void
     */
    public function testGetFacadeAccessor(): void
    {
        $this->assertSame(ConfigSourceService::class, ConfigSource::getFacadeAccessor());
    }

    /**
     * Test a static call forwards to the config source service.
     *
     * @return void
     */
    public function testStaticCallForwardsToService(): void
    {
        $app = $this->makeApp();
        AbstractFacade::setFacadeBase($app);

        $config = ConfigSource::fromArray(['app' => ['debug' => true]])->build();

        $this->assertSame(true, $config->get('app.debug'));
    }

    /**
     * Test a statically registered macro is callable via the facade.
     *
     * @return void
     */
    public function testStaticMacroIsCallableViaFacade(): void
    {
        $app = $this->makeApp();
        AbstractFacade::setFacadeBase($app);

        ConfigSource::macro('fromYaml', function (array $content): ConfigSourceService {
            return $this->fromArray($content);
        });

        $config = ConfigSource::fromYaml(['service' => 'yaml'])->build();

        $this->assertSame('yaml', $config->get('service'));
    }

    /**
     * Test a static call without an application throws.
     *
     * @return void
     */
    public function testStaticCallWithoutApplicationThrows(): void
    {
        $this->expectException(FacadeObjectNotSetException::class);

        ConfigSource::fromArray(['key' => 'value']);
    }

    /**
     * Build an application with config bound.
     *
     * @return Application
     */
    private function makeApp(): Application
    {
        return $this->app = new Application($this->setFixturePath('/fixtures/support/'));
    }
}
