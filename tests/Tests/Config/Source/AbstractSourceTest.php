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

use function compact;

class AbstractSourceTest extends TestCase
{
    /**
     * Test it should fetch file content.
     *
     * @return void
     */
    public function testItShouldFetchFileContent(): void
    {
        $source  = new TestConfigurationSource(slash(path: __DIR__ . '/fixtures/content.txt'));
        $content = "content";

        $this->assertEquals(compact('content'), $source->fetch());
    }

    /**
     * Test it should throw if file not readable.
     *
     * @return void
     */
    public function testItShouldThrowIfFileNotReadable(): void
    {
        $this->expectException(FileReadException::class);

        new TestConfigurationSource(slash(path: __DIR__ . '/fixtures/not-found.txt'))->fetch();
    }
}
