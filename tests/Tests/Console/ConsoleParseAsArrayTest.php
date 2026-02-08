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

use Omega\Console\Test\TestCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Throwable;

use function explode;

/**
 * Tests parsing console input into an array-like command representation.
 *
 * This test suite verifies that console arguments are correctly parsed and
 * exposed through array access, ensuring proper handling of command names,
 * short and long options, and their values. It also asserts that the parsed
 * command structure is immutable and that invalid modifications result in
 * appropriate exceptions.
 *
 * @category  Tests
 * @package   Console
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(TestCommand::class)]
final class ConsoleParseAsArrayTest extends TestCase
{
    /**
     * Test it can parse normal command with space.
     *
     * @return void
     */
    public function testItCanParseNormalCommandWithSpace()
    {
        $command = 'php omega test --n john -t -s --who-is children';
        $argv    = explode(' ', $command);
        $cli     = new TestCommand($argv);

        $this->assertEquals(
            'test',
            $cli['name'],
            'valid parse name: test'
        );

        $this->assertEquals(
            'john',
            $cli['n'],
            'valid parse from short param with sparte space: --n'
        );

        $this->assertTrue(
            isset($cli['who-is']),
            'valid parse from long param: --who-is'
        );
    }

    /**
     * Test it will throw exception when change command.
     *
     * @return void
     */
    public function testItWillThrowExceptionWhenChangeCommand(): void
    {
        $command = 'php omega test --n john -t -s --who-is children';
        $argv    = explode(' ', $command);
        $cli     = new TestCommand($argv);

        try {
            $cli['name'] = 'taylor';
        } catch (Throwable $th) {
            $this->assertEquals('Command cant be modify', $th->getMessage());
        }
    }

    /**
     * Test it will throw exception when unset command.
     *
     * @return void
     */
    public function testItWillThrowExceptionWhenUnsetCommand(): void
    {
        $command = 'php omega test --n john -t -s --who-is children';
        $argv    = explode(' ', $command);
        $cli     = new TestCommand($argv);

        try {
            unset($cli['name']);
        } catch (Throwable $th) {
            $this->assertEquals('Command cant be modify', $th->getMessage());
        }
    }

    /**
     * Test it can check option has exit or not.
     *
     * @return void
     */
    public function testItCanCheckOptionHasExitOrNot(): void
    {
        $command = 'php omega test --true="false"';
        $argv    = explode(' ', $command);
        $cli     = new TestCommand($argv);

        $this->assertTrue((fn () => $this->{'hasOption'}('true'))->call($cli));
        $this->assertFalse((fn () => $this->{'hasOption'}('not-exist'))->call($cli));
    }
}
