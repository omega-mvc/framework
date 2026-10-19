<?php

declare(strict_types=1);

namespace Tests\Logging;

use Omega\Logging\Exception\UnknownDriverException;
use Omega\Logging\LoggingManager;
use Omega\Logging\Stream;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Tests\TestCase;

#[CoversClass(LoggingManager::class)]
final class LoggingManagerTest extends TestCase
{
    public function testRegistersTheDefaultDriverOnConstruction(): void
    {
        $driver  = $this->createStub(LoggerInterface::class);
        $manager = new LoggingManager('stream', $driver);

        $this->assertSame($driver, $manager->getDriver());
        $this->assertSame($driver, $manager->getDriver(null));
        $this->assertSame($driver, $manager->getDriver('stream'));
    }

    public function testThrowsAnExceptionForAnUnknownDriver(): void
    {
        $manager = new LoggingManager('stream', $this->createStub(LoggerInterface::class));

        $this->expectException(UnknownDriverException::class);
        $this->expectExceptionMessage('The log driver "missing" could not be resolved or is not registered.');

        $manager->getDriver('missing');
    }

    public function testSetsADriverWithAnInstance(): void
    {
        $driver  = $this->createStub(LoggerInterface::class);
        $manager = new LoggingManager('stream', $this->createStub(LoggerInterface::class));

        $this->assertSame($manager, $manager->setDriver('custom', $driver));
        $this->assertSame($driver, $manager->getDriver('custom'));
    }

    public function testResolvesAClosureDriverLazilyAndCachesIt(): void
    {
        $driver  = $this->createStub(LoggerInterface::class);
        $manager = new LoggingManager('stream', $this->createStub(LoggerInterface::class));
        $calls   = 0;

        $manager->setDriver('lazy', static function () use (&$calls, $driver): LoggerInterface {
            ++$calls;

            return $driver;
        });

        $first  = $manager->getDriver('lazy');
        $second = $manager->getDriver('lazy');

        $this->assertSame($driver, $first);
        $this->assertSame($driver, $second);
        $this->assertSame(1, $calls);
    }

    public function testSetsTheDefaultDriver(): void
    {
        $initial = $this->createStub(LoggerInterface::class);
        $driver  = $this->createStub(LoggerInterface::class);
        $manager = new LoggingManager('stream', $initial);

        $this->assertSame($manager, $manager->setDefaultDriver($driver));
        $this->assertSame($driver, $manager->getDriver());
    }

    public function testThrowsAnExceptionWhenAClosureDriverResolvesToNull(): void
    {
        $manager = new LoggingManager('stream', $this->createStub(LoggerInterface::class));
        $manager->setDriver('broken', static fn (): ?LoggerInterface => null);

        $this->expectException(UnknownDriverException::class);
        $this->expectExceptionMessage('The log driver "broken" could not be resolved or is not registered.');

        $manager->getDriver('broken');
    }

    public function testDelegatesPsr3LevelMethodsToTheDefaultDriver(): void
    {
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
    }

    public function testForwardsMagicMethodCallsToTheDefaultDriver(): void
    {
        $driver = $this->getMockBuilder(Stream::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getLogFilePath'])
            ->getMock();

        $driver->expects($this->once())
            ->method('getLogFilePath')
            ->willReturn('/tmp/omega.log');

        $manager = new LoggingManager('stream', $driver);

        $this->assertSame('/tmp/omega.log', $manager->getLogFilePath());
    }
}
