<?php

/**
 * Part of Omega - Tests\Config Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Config\Source;

use Omega\Config\Exceptions\FileReadException;
use PHPUnit\Framework\TestCase;
use Tests\FixturesPathTrait;

use function compact;

/**
 * Test suite for validating the behavior of AbstractSource implementations.
 *
 * These tests ensure that a concrete source correctly reads file content
 * and properly handles unreadable or missing files by throwing the expected
 * exceptions. The suite verifies both the happy path and failure scenarios
 * to guarantee predictable behavior for all file-based configuration sources.
 *
 * @category   Tests
 * @package    Config
 * @subpackage Source
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
final class SourceTest extends TestCase
{
    use FixturesPathTrait;

    /**
     * Test it should fetch file content.
     *
     * @return void
     */
    public function testItShouldFetchFileContent(): void
    {
        $source = new TestConfigurationSource($this->setFixturePath(slash(path: '/fixtures/config/content.txt')));

        $content = 'content';

        $this->assertEquals(
            compact('content'),
            $source->fetch()
        );
    }

    /**
     * Test it should throw if file not readable.
     *
     * @return void
     */
    public function testItShouldThrowIfFileNotReadable(): void
    {
        $this->expectException(FileReadException::class);

        new TestConfigurationSource($this->setFixturePath(slash(path: '/fixtures/config/not-found.txt')))->fetch();
    }
}
