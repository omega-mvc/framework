<?php

declare(strict_types=1);

namespace Tests\Application;

use ArrayObject;
use LogicException;
use Omega\Application\AbstractApplication;
use Omega\Application\Application;
use Omega\Config\Bootstrapper\ConfigBootstrapper;
use Omega\View\Templator;
use PHPUnit\Framework\Attributes\CoversClass;
use stdClass;
use Tests\Application\Fixtures\TestBootstrapProvider;
use Tests\TestCase;

#[CoversClass(AbstractApplication::class)]
final class AbstractApplicationTest extends TestCase
{
    public function testReturnsAnEmptyStringWhenNoNameIsBound(): void
    {
        $app = new Application('/');

        $this->assertSame('', $app->getName());

        $app->flush();
    }

    public function testReturnsAnEmptyStringWhenBoundNameIsNotAString(): void
    {
        $app = new Application('/');

        $app->set('app.name', 123);

        $this->assertSame('', $app->getName());

        $app->flush();
    }

    public function testReturnsTheBoundApplicationName(): void
    {
        $app = new Application('/');

        $app->set('app.name', 'Omega');

        $this->assertSame('Omega', $app->getName());

        $app->flush();
    }

    public function testClearsRequestScopedStateAndDependenciesOnResetForRequest(): void
    {
        $app = new Application('/');

        $app->registerTerminate(static function (): void {
            echo 'terminate.';
        });
        $app->bootingCallback(static function (): void {
            echo 'booting.';
        });
        $app->bootedCallback(static function (): void {
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

    public function testResetsRequestStateWithoutTemplatorViewInstance(): void
    {
        $app = new Application('/');

        $app->resetForRequest();

        $this->assertFalse($app->isBooted);

        $app->flush();
    }

    public function testReturnsAnEmptyStringWhenNoVersionIsBound(): void
    {
        $app = new Application('/');

        $this->assertSame('', $app->getVersion());

        $app->flush();
    }

    public function testReturnsAnEmptyStringWhenBoundVersionIsNotAString(): void
    {
        $app = new Application('/');

        $app->set('app.version', 123);

        $this->assertSame('', $app->getVersion());

        $app->flush();
    }

    public function testReturnsTheBoundApplicationVersion(): void
    {
        $app = new Application('/');

        $app->set('app.version', '1.0.0');

        $this->assertSame('1.0.0', $app->getVersion());

        $app->flush();
    }

    public function testReturnsAnEmptyStringWhenNoEnvironmentIsBound(): void
    {
        $app = new Application('/');

        $this->assertSame('', $app->getEnvironment());

        $app->flush();
    }

    public function testReturnsAnEmptyStringWhenBoundEnvironmentIsNotAString(): void
    {
        $app = new Application('/');

        $app->set('environment', 123);

        $this->assertSame('', $app->getEnvironment());

        $app->flush();
    }

    public function testReturnsTheBoundEnvironment(): void
    {
        $app = new Application('/');

        $app->set('environment', 'production');

        $this->assertSame('production', $app->getEnvironment());

        $app->flush();
    }

    public function testReturnsFalseWhenNoDebugValueIsBound(): void
    {
        $app = new Application('/');

        $this->assertFalse($app->isDebugMode());

        $app->flush();
    }

    public function testReturnsFalseWhenBoundDebugValueIsNotBoolean(): void
    {
        $app = new Application('/');

        $app->set('app.debug', 'true');

        $this->assertFalse($app->isDebugMode());

        $app->flush();
    }

    public function testReturnsTrueWhenDebugIsBoundToTrue(): void
    {
        $app = new Application('/');

        $app->set('app.debug', true);

        $this->assertTrue($app->isDebugMode());

        $app->flush();
    }

    public function testReturnsFalseWhenDebugIsBoundToFalse(): void
    {
        $app = new Application('/');

        $app->set('app.debug', false);

        $this->assertFalse($app->isDebugMode());

        $app->flush();
    }

    public function testThrowsLogicExceptionWhenSetBaseBindingReceivesNonApplicationInstance(): void
    {
        $this->expectException(LogicException::class);

        new class ('/') extends AbstractApplication {
            public function registerAlias(): void
            {
            }

            public function isDownMaintenanceMode(): bool
            {
                return false;
            }

            public function getDownData(): array
            {
                return [];
            }

            public function abort(int $code, string $message = '', array $headers = []): void
            {
            }
        };
    }

    public function testIsNoOpWhenApplicationIsAlreadyBooted(): void
    {
        $app = new Application(__DIR__ . '/fixtures/application-read/');

        new ConfigBootstrapper()->bootstrap($app);

        $app->bootProvider();
        $app->bootProvider();

        $this->assertTrue($app->isBooted);

        $app->flush();
    }

    public function testIgnoresNonObjectInstancesAndObjectsWithoutBootstrapMethod(): void
    {
        $app = new Application('/');

        $app->set(ArrayObject::class, 'scalar-value');

        ob_start();
        $app->bootstrapWith([
            ArrayObject::class,
            stdClass::class,
            TestBootstrapProvider::class,
        ]);
        $out = ob_get_clean();

        $this->assertSame('Tests\Application\Fixtures\TestBootstrapProvider::bootstrap', $out);
        $this->assertTrue($app->bootstrapped);

        $app->flush();
    }

    public function testWorksWhenViewInstanceIsNotTemplator(): void
    {
        $app = new Application('/');

        $app->set('view.instance', new stdClass());

        $app->resetForRequest();

        $this->assertFalse($app->isBooted);

        $app->flush();
    }
}