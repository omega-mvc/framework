<?php

/**
 * Part of Omega - Tests\Console Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Console;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Omega\Console\CommandMap;
use Throwable;

/**
 * Test suite for the CommandMap component.
 *
 * Verifies that command mappings correctly resolve commands, modes,
 * classes, callbacks, matching logic, defaults, and error conditions
 * used by the console command dispatcher.
 *
 * @category  Tests
 * @package   Console
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(CommandMap::class)]
class CommandMapTest extends TestCase
{
    /**
     * Test it can get cmd.
     *
     * @return void
     */
    public function testItCanGetCmd(): void
    {
        $command = new CommandMap([
            'cmd' => 'test:test',
        ]);

        $this->assertEquals(['test:test'], $command->cmd());
    }

    /**
     * Test it can get mode.
     *
     * @return void
     */
    public function testItCanGetMode(): void
    {
        $command = new CommandMap([
            'cmd'  => 'test:test',
            'mode' => 'full',
        ]);

        $this->assertEquals('full', $command->mode());
    }

    /**
     * Test it can get mode default.
     *
     * @return void
     */
    public function testItCanGetModeDefault(): void
    {
        $command = new CommandMap([]);

        $this->assertEquals('full', $command->mode());
    }

    /**
     * Test it can get class.
     *
     * @return void
     */
    public function testItCanGetClass(): void
    {
        $command = new CommandMap([
            'class' => 'testclass',
        ]);

        $this->assertEquals('testclass', $command->class());
    }

    /**
     * Test it can get class using fn.
     *
     * @return void
     */
    public function testItCanGetClassUsingFn(): void
    {
        $command = new CommandMap([
            'fn' => ['testclass', 'main'],
        ]);

        $this->assertEquals('testclass', $command->class());
    }

    /**
     * Test it will throw error when fn in array but class not exists.
     *
     * @return void
     */
    public function testItWillThrowErrorWhenFnIsArrayButClassNotExist(): void
    {
        $command = new CommandMap([
            'fn' => [],
        ]);

        try {
            $command->class();
        } catch (Throwable $th) {
            $this->assertEquals('Command map require class in (class or fn).', $th->getMessage());
        }
    }

    /**
     * Test it will throw error when class not exists.
     *
     * @return void
     */
    public function testItWillThrowErrorWhenClassNotExist(): void
    {
        $command = new CommandMap([]);

        try {
            $command->class();
        } catch (Throwable $th) {
            $this->assertEquals('Command map require class in (class or fn).', $th->getMessage());
        }
    }

    /**
     * Test it can get fn.
     *
     * @return void
     */
    public function testItCanGetFn(): void
    {
        $command = new CommandMap([
            'fn' => ['testclass', 'main'],
        ]);

        $this->assertEquals(['testclass', 'main'], $command->fn());
    }

    /**
     * Test it can get fn default.
     *
     * @return void
     */
    public function testItCanGetFnDefault(): void
    {
        $command = new CommandMap([]);

        $this->assertEquals('main', $command->fn());
    }

    /**
     * Test it can get default option.
     *
     * @return void
     */
    public function testItCanGetDefaultOption(): void
    {
        $command = new CommandMap([]);

        $this->assertEquals('main', $command->fn());
    }

    /**
     * Test it can match callback using pattern.
     *
     * @return void
     */
    public function testItCanMatchCallbackUsingPattern(): void
    {
        $command = new CommandMap([
            'pattern' => 'test:test',
        ]);

        $this->assertTrue(($command->match())('test:test'));
    }

    /**
     * Test it can match callback using match.
     *
     * @return void
     */
    public function testItCanMatchCallbackUsingMatch(): void
    {
        $command = new CommandMap([
            'match' => fn ($given) => true,
        ]);

        $this->assertTrue(($command->match())('always_true'));
    }

    /**
     * Test it can match callback using cmd full.
     *
     * @return void
     */
    public function testItCanMatchCallbackUsingCmdFull(): void
    {
        $command = new CommandMap([
            'cmd' => ['test:test', 'test:start'],
        ]);

        $this->assertTrue(($command->match())('test:test'));
    }

    /**
     * Test it can match callback using cmd start.
     *
     * @return void
     */
    public function testItCanMatchCallbackUsingCmdStart(): void
    {
        $command = new CommandMap([
            'cmd'  => ['make:', 'test:'],
            'mode' => 'start',
        ]);

        $this->assertTrue(($command->match())('test:unit'));
    }

    /**
     * Test it can call is match.
     *
     * @return void
     */
    public function testItCanCallIsMatch(): void
    {
        $command = new CommandMap([
            'cmd'  => 'test:unit',
        ]);

        $this->assertTrue($command->isMatch('test:unit'));
    }

    /**
     * Test it can get call using fn.
     *
     * @return void
     */
    public function testItCanGetCallUsingFn(): void
    {
        $command = new CommandMap([
            'fn'  => ['someclass', 'main'],
        ]);

        $this->assertEquals(['someclass', 'main'], $command->call());
    }

    /**
     * Test it can get call using class.
     *
     * @return void
     */
    public function testItCanGetCallUsingClass(): void
    {
        $command = new CommandMap([
            'class' => 'someclass',
            // skip 'fn' because default if 'main'
        ]);

        $this->assertEquals(['someclass', 'main'], $command->call());
    }
}
