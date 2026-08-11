<?php

/**
 * Part of Omega - Tests\Logging Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Logging;

use Omega\Logging\Exception\UnknownDriverException;
use Omega\Logging\LoggingManager;
use Omega\Logging\Stream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Psr\Log\LoggerInterface;

/**
 * Class LoggingManagerTest
 *
 * This test suite verifies the behavior of the {@see LoggingManager} class:
 * driver registration and resolution, default driver handling, PSR-3
 * delegation and magic method forwarding.
 *
 * @category   Tests
 * @package    Logging
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[CoversClass(LoggingManager::class)]
final class LoggingManagerTest extends TestCase
{
    /**
     * Test default driver is registered on construction.
     *
     * @return void
     */
    public function testDefaultDriverIsRegisteredOnConstruction(): void
    {
        $driver  = $this->createMock(LoggerInterface::class);
        $manager = new LoggingManager('stream', $driver);

        $this->assertSame($driver, $manager->getDriver());
        $this->assertSame($driver, $manager->getDriver(null));
        $this->assertSame($driver, $manager->getDriver('stream'));
    }

    /**
     * Test unknown driver throws.
     *
     * @return void
     */
    public function testUnknownDriverThrows(): void
    {
        $manager = new LoggingManager('stream', $this->createMock(LoggerInterface::class));

        $this->expectException(UnknownDriverException::class);
        $this->expectExceptionMessage('The log driver "missing" could not be resolved or is not registered.');

        $manager->getDriver('missing');
    }

    /**
     * Test set driver with an instance.
     *
     * @return void
     */
    public function testSetDriverWithInstance(): void
    {
        $driver  = $this->createMock(LoggerInterface::class);
        $manager = new LoggingManager('stream', $this->createMock(LoggerInterface::class));

        $this->assertSame($manager, $manager->setDriver('custom', $driver));
        $this->assertSame($driver, $manager->getDriver('custom'));
    }

    /**
     * Test set driver with a closure is resolved lazily and cached.
     *
     * @return void
     */
    public function testSetDriverWithClosureIsCached(): void
    {
        $driver  = $this->createMock(LoggerInterface::class);
        $manager = new LoggingManager('stream', $this->createMock(LoggerInterface::class));
        $calls   = 0;

        $manager->setDriver('lazy', static function () use (&$calls, $driver): LoggerInterface {
            ++$calls;

            return $driver;
        });

        $this->assertSame($driver, $manager->getDriver('lazy'));
        $this->assertSame($driver, $manager->getDriver('lazy'));
        $this->assertSame(1, $calls);
    }

    /**
     * Test set default driver.
     *
     * @return void
     */
    public function testSetDefaultDriver(): void
    {
        $initial = $this->createMock(LoggerInterface::class);
        $driver  = $this->createMock(LoggerInterface::class);
        $manager = new LoggingManager('stream', $initial);

        $this->assertSame($manager, $manager->setDefaultDriver($driver));
        $this->assertSame($driver, $manager->getDriver());
    }

    /**
     * Test a closure driver resolving to null throws.
     *
     * @return void
     */
    public function testClosureResolvingToNullThrows(): void
    {
        $manager = new LoggingManager('stream', $this->createMock(LoggerInterface::class));
        $manager->setDriver('broken', static fn (): ?LoggerInterface => null);

        $this->expectException(UnknownDriverException::class);
        $this->expectExceptionMessage('The log driver "broken" could not be resolved or is not registered.');

        $manager->getDriver('broken');
    }

    /**
     * Test PSR-3 level methods delegate to the default driver.
     *
     * @return void
     */
    public function testPsr3LevelMethodsDelegateToDefaultDriver(): void
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

    /**
     * Test magic method forwards calls to the default driver.
     *
     * @return void
     */
    public function testMagicMethodForwardsToDefaultDriver(): void
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
