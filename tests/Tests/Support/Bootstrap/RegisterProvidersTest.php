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

/** @noinspection PhpExpressionResultUnusedInspection */

declare(strict_types=1);

namespace Tests\Support\Bootstrap;

use Exception;
use Omega\Application\Application;
use Omega\Config\ConfigRepository;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Container\Provider\AbstractServiceProvider;
use Omega\Support\Bootstrap\BootProviders;
use Omega\Support\Bootstrap\RegisterProviders;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use Tests\FixturesPathTrait;
use Tests\Support\Bootstrap\Support\TestRegisterProvider;
use Tests\Support\Bootstrap\Support\TestRegisterServiceProvider;

/**
 * Class RegisterProvidersTest
 *
 * This test suite verifies that service providers can be correctly registered
 * and booted within the Application lifecycle. It ensures that providers added
 * at runtime are included in the boot sequence alongside default and vendor
 * providers, and that the final list of booted providers reflects all expected
 * entries.
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
#[CoversClass(AbstractServiceProvider::class)]
#[CoversClass(Application::class)]
#[CoversClass(BindingResolutionException::class)]
#[CoversClass(BootProviders::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(EntryNotFoundException::class)]
#[CoversClass(RegisterProviders::class)]
final class RegisterProvidersTest extends TestCase
{
    use FixturesPathTrait;

    /**
     * Test bootstrap.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception if a generic error occurred
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testBootstrap(): void
    {
        $app = new Application($this->setFixturePath('/fixtures/support/'));
        $app->register(TestRegisterServiceProvider::class);
        $app->bootstrapWith([BootProviders::class]);

        $this->assertCount(
            3,
            (fn () => $this->{'bootedProviders'})->call($app),
            '1 from default provider, 1 from this test, and 1 from vendor.'
        );
    }

    /**
     * Test boot provider continue line is covered.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception if a generic error occurred
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testBootProviderContinueLineIsCovered(): void
    {
        $app = new Application($this->setFixturePath('/fixtures/support/'));
        $provider = TestRegisterServiceProvider::class;

        $app->register($provider);

        (fn () => $this->{'bootedProviders'}[] = $provider)->call($app);

        $app->bootstrapWith([BootProviders::class]);

        $booted = (fn () => $this->{'bootedProviders'})->call($app);

        $this->assertContains($provider, $booted);
    }

    public function testRegisterProviderCallsRegisterMethod(): void
    {
        TestRegisterProvider::$called = 0;

        $app = new Application($this->setFixturePath(slash(path: '/fixtures/application-read/')));

        $ref = new ReflectionProperty($app, 'providers');
        $ref->setAccessible(true);
        $ref->setValue($app, [TestRegisterProvider::class]);

        $app->registerProvider();

        $this->assertSame(1, TestRegisterProvider::$called);
    }

    public function testRegisterProviderSkipsLoadedProviders(): void
    {
        TestRegisterProvider::$called = 0;

        $app = new Application($this->setFixturePath(slash(path: '/fixtures/application-read/')));

        $ref = new ReflectionProperty($app, 'providers');
        $ref->setAccessible(true);
        $ref->setValue($app, [TestRegisterProvider::class]);

        $loaded = new ReflectionProperty($app, 'loadedProviders');
        $loaded->setAccessible(true);
        $loaded->setValue($app, [TestRegisterProvider::class]);

        $app->registerProvider();

        $this->assertSame(0, TestRegisterProvider::$called);
    }

    public function testBootstrapRegistersProviders(): void
    {
        // 1. Setup dell'applicazione
        $app = new Application($this->setFixturePath('/fixtures/support/'));

        // 2. Fai in modo che il provider sia presente nell'array dei provider dell'app.
        // Poiché $providers è protetto, usiamo il metodo di test che hai già
        // o manipoliamo la configurazione se possibile.
        // Se non puoi accedere direttamente, aggiungi un metodo temporaneo
        // o usa un file di configurazione di test nella cartella 'fixtures'.

        $app->loadConfig(new ConfigRepository([
            'providers' => [TestRegisterServiceProvider::class],
            'VIEW_EXTENSIONS' => [] // Necessario se il costruttore lo richiede
        ]));

        // 3. Esegui il bootstrapper
        $bootstrapper = new RegisterProviders();
        $bootstrapper->bootstrap($app);

        // 4. Verifica che il provider sia stato registrato
        $this->assertTrue(
            $this->isProviderLoaded($app, TestRegisterServiceProvider::class),
            'Il provider non è stato caricato correttamente.'
        );
    }

    private function isProviderLoaded(Application $app, string $providerClass): bool
    {
        $reflection = new ReflectionClass($app);
        $property = $reflection->getProperty('loadedProviders');
        $property->setAccessible(true);
        $loaded = $property->getValue($app);

        return in_array($providerClass, $loaded);
    }
}
