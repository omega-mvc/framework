<?php

declare(strict_types=1);

namespace Tests\Logging;

use Omega\Logging\Exception\UnknownDriverException;
use Omega\Logging\LoggingManager;
use Omega\Logging\Stream;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

use function count;

covers(LoggingManager::class);

it('registers the default driver on construction', function (): void {
    $driver  = $this->createStub(LoggerInterface::class);
    $manager = new LoggingManager('stream', $driver);

    expect($manager->getDriver())->toBe($driver);
    expect($manager->getDriver(null))->toBe($driver);
    expect($manager->getDriver('stream'))->toBe($driver);
});

it('throws an exception for an unknown driver', function (): void {
    $manager = new LoggingManager('stream', $this->createStub(LoggerInterface::class));

    $manager->getDriver('missing');
})->throws(UnknownDriverException::class, 'The log driver "missing" could not be resolved or is not registered.');

it('sets a driver with an instance', function (): void {
    $driver  = $this->createStub(LoggerInterface::class);
    $manager = new LoggingManager('stream', $this->createStub(LoggerInterface::class));

    expect($manager->setDriver('custom', $driver))->toBe($manager);
    expect($manager->getDriver('custom'))->toBe($driver);
});

it('resolves a closure driver lazily and caches it', function (): void {
    $driver  = $this->createStub(LoggerInterface::class);
    $manager = new LoggingManager('stream', $this->createStub(LoggerInterface::class));
    $calls   = 0;

    $manager->setDriver('lazy', static function () use (&$calls, $driver): LoggerInterface {
        ++$calls;

        return $driver;
    });

    expect($manager->getDriver('lazy'))->toBe($driver);
    expect($manager->getDriver('lazy'))->toBe($driver);
    expect($calls)->toBe(1);
});

it('sets the default driver', function (): void {
    $initial = $this->createStub(LoggerInterface::class);
    $driver  = $this->createStub(LoggerInterface::class);
    $manager = new LoggingManager('stream', $initial);

    expect($manager->setDefaultDriver($driver))->toBe($manager);
    expect($manager->getDriver())->toBe($driver);
});

it('throws an exception when a closure driver resolves to null', function (): void {
    $manager = new LoggingManager('stream', $this->createStub(LoggerInterface::class));
    $manager->setDriver('broken', static fn (): ?LoggerInterface => null);

    $manager->getDriver('broken');
})->throws(UnknownDriverException::class, 'The log driver "broken" could not be resolved or is not registered.');

it('delegates psr-3 level methods to the default driver', function (): void {
    $driver = $this->createMock(LoggerInterface::class);

    $driver->expects($this->once())->method('emergency')->with('msg', ['a' => 1]);
    $driver->expects($this->once())->method('alert')->with('msg', ['a' => 1]);
    $driver->expects($this->once())->method('critical')->with('msg', ['a' => 1]);
    $driver->expects($this->once())->method('error')->with('msg', ['a' => 1]);
    $driver->expects($this->once())->method('warning')->with('msg', ['a' => 1]);
    $driver->expects($this->once())->method('notice')->with('msg', ['a' => 1]);
    $driver->expects($this->once())->method('info')->with('msg', ['a' => 1]);
    $driver->expects($this->once())->method('debug')->with('msg', ['a' => 1]);
    $driver->expects($this->once())->method('log')->with(LogLevel::WARNING, 'msg', ['a' => 1]);

    $manager = new LoggingManager('stream', $driver);

    $manager->emergency('msg', ['a' => 1]);
    $manager->alert('msg', ['a' => 1]);
    $manager->critical('msg', ['a' => 1]);
    $manager->error('msg', ['a' => 1]);
    $manager->warning('msg', ['a' => 1]);
    $manager->notice('msg', ['a' => 1]);
    $manager->info('msg', ['a' => 1]);
    $manager->debug('msg', ['a' => 1]);
    $manager->log(LogLevel::WARNING, 'msg', ['a' => 1]);
});

it('forwards magic method calls to the default driver', function (): void {
    $driver = $this->getMockBuilder(Stream::class)
        ->disableOriginalConstructor()
        ->onlyMethods(['getLogFilePath'])
        ->getMock();

    $driver->expects($this->once())
        ->method('getLogFilePath')
        ->willReturn('/tmp/omega.log');

    $manager = new LoggingManager('stream', $driver);

    expect($manager->getLogFilePath())->toBe('/tmp/omega.log');
});