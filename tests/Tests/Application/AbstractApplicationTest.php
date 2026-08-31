<?php

/**
 * Part of Omega - Tests\Application Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Application;

use Omega\Application\AbstractApplication;
use Omega\Application\Application;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\View\Templator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;

/**
 * Test suite for the AbstractApplication request lifecycle methods.
 *
 * This class covers methods of the application container that are not
 * exercised by any other test: request state resetting, route bootstrapping
 * and application name resolution.
 *
 * @category  Tests
 * @package   Application
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(AbstractApplication::class)]
class AbstractApplicationTest extends TestCase
{
    /**
     * Test getName returns an empty string when no name is bound.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws CircularAliasException
     * @throws ContainerExceptionInterface
     * @throws EntryNotFoundException
     * @throws ReflectionException
     */
    public function testGetNameReturnsEmptyStringWhenNotBound(): void
    {
        $app = new Application('/');

        $this->assertSame('', $app->getName());

        $app->flush();
    }

    /**
     * Test getName returns an empty string when the bound name is not a string.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws CircularAliasException
     * @throws ContainerExceptionInterface
     * @throws EntryNotFoundException
     * @throws ReflectionException
     */
    public function testGetNameReturnsEmptyStringWhenNotAString(): void
    {
        $app = new Application('/');

        $app->set('app.name', 123);

        $this->assertSame('', $app->getName());

        $app->flush();
    }

    /**
     * Test getName returns the bound application name.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws CircularAliasException
     * @throws ContainerExceptionInterface
     * @throws EntryNotFoundException
     * @throws ReflectionException
     */
    public function testGetNameReturnsBoundName(): void
    {
        $app = new Application('/');

        $app->set('app.name', 'Omega');

        $this->assertSame('Omega', $app->getName());

        $app->flush();
    }

    /**
     * Test resetForRequest clears request-scoped state and dependencies.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws CircularAliasException
     * @throws ContainerExceptionInterface
     * @throws EntryNotFoundException
     * @throws ReflectionException
     */
    public function testResetForRequestClearsStateAndTemplator(): void
    {
        $app = new Application('/');

        $app->registerTerminate(static function () {
            echo 'terminate.';
        });
        $app->bootingCallback(static function () {
            echo 'booting.';
        });
        $app->bootedCallback(static function () {
            echo 'booted.';
        });

        $templator = new class extends Templator {
            public bool $cleared = false;

            public function __construct()
            {
            }

            public function clearDependencies(): Templator
            {
                $this->cleared = true;

                return $this;
            }
        };

        $app->set('view.instance', $templator);

        $app->resetForRequest();

        $this->assertTrue($templator->cleared);

        ob_start();
        $app->terminate();
        $out = ob_get_clean();

        $this->assertSame('', $out);

        $app->flush();
    }

    /**
     * Test resetForRequest without a Templator view instance.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws CircularAliasException
     * @throws ContainerExceptionInterface
     * @throws EntryNotFoundException
     * @throws ReflectionException
     */
    public function testResetForRequestWithoutTemplator(): void
    {
        $app = new Application('/');

        $app->resetForRequest();

        $this->assertFalse($app->isBooted);

        $app->flush();
    }

    /**
     * Test getVersion returns an empty string when no version is bound.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws CircularAliasException
     * @throws ContainerExceptionInterface
     * @throws EntryNotFoundException
     * @throws ReflectionException
     */
    public function testGetVersionReturnsEmptyStringWhenNotBound(): void
    {
        $app = new Application('/');

        $this->assertSame('', $app->getVersion());

        $app->flush();
    }

    /**
     * Test getVersion returns an empty string when the bound version is not a string.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws CircularAliasException
     * @throws ContainerExceptionInterface
     * @throws EntryNotFoundException
     * @throws ReflectionException
     */
    public function testGetVersionReturnsEmptyStringWhenNotAString(): void
    {
        $app = new Application('/');

        $app->set('app.version', 123);

        $this->assertSame('', $app->getVersion());

        $app->flush();
    }

    /**
     * Test getVersion returns the bound application version.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws CircularAliasException
     * @throws ContainerExceptionInterface
     * @throws EntryNotFoundException
     * @throws ReflectionException
     */
    public function testGetVersionReturnsBoundVersion(): void
    {
        $app = new Application('/');

        $app->set('app.version', '1.0.0');

        $this->assertSame('1.0.0', $app->getVersion());

        $app->flush();
    }

    /**
     * Test getEnvironment returns an empty string when no environment is bound.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws CircularAliasException
     * @throws ContainerExceptionInterface
     * @throws EntryNotFoundException
     * @throws ReflectionException
     */
    public function testGetEnvironmentReturnsEmptyStringWhenNotBound(): void
    {
        $app = new Application('/');

        $this->assertSame('', $app->getEnvironment());

        $app->flush();
    }

    /**
     * Test getEnvironment returns an empty string when the bound environment is not a string.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws CircularAliasException
     * @throws ContainerExceptionInterface
     * @throws EntryNotFoundException
     * @throws ReflectionException
     */
    public function testGetEnvironmentReturnsEmptyStringWhenNotAString(): void
    {
        $app = new Application('/');

        $app->set('environment', 123);

        $this->assertSame('', $app->getEnvironment());

        $app->flush();
    }

    /**
     * Test getEnvironment returns the bound environment.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws CircularAliasException
     * @throws ContainerExceptionInterface
     * @throws EntryNotFoundException
     * @throws ReflectionException
     */
    public function testGetEnvironmentReturnsBoundEnvironment(): void
    {
        $app = new Application('/');

        $app->set('environment', 'production');

        $this->assertSame('production', $app->getEnvironment());

        $app->flush();
    }

    /**
     * Test isDebugMode returns false when no debug value is bound.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws CircularAliasException
     * @throws ContainerExceptionInterface
     * @throws EntryNotFoundException
     * @throws ReflectionException
     */
    public function testIsDebugModeReturnsFalseWhenNotBound(): void
    {
        $app = new Application('/');

        $this->assertFalse($app->isDebugMode());

        $app->flush();
    }

    /**
     * Test isDebugMode returns false when the bound debug value is not a boolean.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws CircularAliasException
     * @throws ContainerExceptionInterface
     * @throws EntryNotFoundException
     * @throws ReflectionException
     */
    public function testIsDebugModeReturnsFalseWhenNotABoolean(): void
    {
        $app = new Application('/');

        $app->set('app.debug', 'true');

        $this->assertFalse($app->isDebugMode());

        $app->flush();
    }

    /**
     * Test isDebugMode returns true when debug is bound to true.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws CircularAliasException
     * @throws ContainerExceptionInterface
     * @throws EntryNotFoundException
     * @throws ReflectionException
     */
    public function testIsDebugModeReturnsTrueWhenBound(): void
    {
        $app = new Application('/');

        $app->set('app.debug', true);

        $this->assertTrue($app->isDebugMode());

        $app->flush();
    }

    /**
     * Test isDebugMode returns false when debug is bound to false.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws CircularAliasException
     * @throws ContainerExceptionInterface
     * @throws EntryNotFoundException
     * @throws ReflectionException
     */
    public function testIsDebugModeReturnsFalseWhenBoundToFalse(): void
    {
        $app = new Application('/');

        $app->set('app.debug', false);

        $this->assertFalse($app->isDebugMode());

        $app->flush();
    }
}
