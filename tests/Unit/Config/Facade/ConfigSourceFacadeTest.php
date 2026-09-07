<?php

declare(strict_types=1);

namespace Tests\Config\Facade;

use Omega\Application\Application;
use Omega\Config\ConfigSource as ConfigSourceService;
use Omega\Config\Facade\ConfigSource;
use Omega\Facade\AbstractFacade;
use Omega\Facade\Exceptions\FacadeObjectNotSetException;
use Tests\FixturesPathTrait;

covers(ConfigSource::class);
covers(ConfigSourceService::class);

uses(FixturesPathTrait::class);

beforeEach(function (): void {
    $this->app = new Application($this->setFixturePath('/fixtures/support/'));
    ConfigSourceService::resetMacro();
});

afterEach(function (): void {
    AbstractFacade::setFacadeBase(null);
    AbstractFacade::flushInstance();

    $this->app->flush();
});

it('gets the facade accessor', function (): void {
    expect(ConfigSource::getFacadeAccessor())->toBe(ConfigSourceService::class);
});

it('forwards a static call to the config source service', function (): void {
    AbstractFacade::setFacadeBase($this->app);

    $config = ConfigSource::fromArray(['app' => ['debug' => true]])->build();

    expect($config->get('app.debug'))->toBe(true);
});

it('makes a statically registered macro callable via the facade', function (): void {
    AbstractFacade::setFacadeBase($this->app);

    ConfigSource::macro('fromYaml', function (array $content): ConfigSourceService {
        return $this->fromArray($content);
    });

    $config = ConfigSource::fromYaml(['service' => 'yaml'])->build();

    expect($config->get('service'))->toBe('yaml');
});

it('throws when a static call is made without an application', function (): void {
    ConfigSource::fromArray(['key' => 'value']);
})->throws(FacadeObjectNotSetException::class);
