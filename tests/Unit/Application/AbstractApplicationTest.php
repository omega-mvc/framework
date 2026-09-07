<?php

declare(strict_types=1);

namespace Tests\Application;

use ArrayObject;
use LogicException;
use Omega\Application\AbstractApplication;
use Omega\Application\Application;
use Omega\Config\Bootstrapper\ConfigBootstrapper;
use Omega\View\Templator;
use stdClass;
use Tests\Application\Fixtures\TestBootstrapProvider;
use Tests\FixturesPathTrait;

covers(AbstractApplication::class);

uses(FixturesPathTrait::class);

it('returns an empty string when no name is bound', function (): void {
    $app = new Application('/');

    expect($app->getName())->toBe('');

    $app->flush();
});

it('returns an empty string when the bound name is not a string', function (): void {
    $app = new Application('/');

    $app->set('app.name', 123);

    expect($app->getName())->toBe('');

    $app->flush();
});

it('returns the bound application name', function (): void {
    $app = new Application('/');

    $app->set('app.name', 'Omega');

    expect($app->getName())->toBe('Omega');

    $app->flush();
});

it('clears request-scoped state and dependencies on resetForRequest', function (): void {
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

    expect($templator->cleared)->toBeTrue();

    ob_start();
    $app->terminate();
    $out = ob_get_clean();

    expect($out)->toBe('');

    $app->flush();
});

it('resets request state without a Templator view instance', function (): void {
    $app = new Application('/');

    $app->resetForRequest();

    expect($app->isBooted)->toBeFalse();

    $app->flush();
});

it('returns an empty string when no version is bound', function (): void {
    $app = new Application('/');

    expect($app->getVersion())->toBe('');

    $app->flush();
});

it('returns an empty string when the bound version is not a string', function (): void {
    $app = new Application('/');

    $app->set('app.version', 123);

    expect($app->getVersion())->toBe('');

    $app->flush();
});

it('returns the bound application version', function (): void {
    $app = new Application('/');

    $app->set('app.version', '1.0.0');

    expect($app->getVersion())->toBe('1.0.0');

    $app->flush();
});

it('returns an empty string when no environment is bound', function (): void {
    $app = new Application('/');

    expect($app->getEnvironment())->toBe('');

    $app->flush();
});

it('returns an empty string when the bound environment is not a string', function (): void {
    $app = new Application('/');

    $app->set('environment', 123);

    expect($app->getEnvironment())->toBe('');

    $app->flush();
});

it('returns the bound environment', function (): void {
    $app = new Application('/');

    $app->set('environment', 'production');

    expect($app->getEnvironment())->toBe('production');

    $app->flush();
});

it('returns false when no debug value is bound', function (): void {
    $app = new Application('/');

    expect($app->isDebugMode())->toBeFalse();

    $app->flush();
});

it('returns false when the bound debug value is not a boolean', function (): void {
    $app = new Application('/');

    $app->set('app.debug', 'true');

    expect($app->isDebugMode())->toBeFalse();

    $app->flush();
});

it('returns true when debug is bound to true', function (): void {
    $app = new Application('/');

    $app->set('app.debug', true);

    expect($app->isDebugMode())->toBeTrue();

    $app->flush();
});

it('returns false when debug is bound to false', function (): void {
    $app = new Application('/');

    $app->set('app.debug', false);

    expect($app->isDebugMode())->toBeFalse();

    $app->flush();
});

it('throws a logic exception when setBaseBinding receives a non-Application instance', function (): void {
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
})->throws(LogicException::class);

it('is a no-op when the application is already booted', function (): void {
    $app = new Application($this->setFixturePath('/fixtures/application-read/'));

    new ConfigBootstrapper()->bootstrap($app);

    $app->bootProvider();
    $app->bootProvider();

    expect($app->isBooted)->toBeTrue();

    $app->flush();
});

it('ignores non-object instances and objects without a bootstrap method', function (): void {
    $app = new Application('/');

    $app->set(ArrayObject::class, 'scalar-value');

    ob_start();
    $app->bootstrapWith([
        ArrayObject::class,
        stdClass::class,
        TestBootstrapProvider::class,
    ]);
    $out = ob_get_clean();

    expect($out)->toBe('Tests\Application\Fixtures\TestBootstrapProvider::bootstrap');
    expect($app->bootstrapped)->toBeTrue();

    $app->flush();
});

it('works when the view instance is not a Templator', function (): void {
    $app = new Application('/');

    $app->set('view.instance', new stdClass());

    $app->resetForRequest();

    expect($app->isBooted)->toBeFalse();

    $app->flush();
});
