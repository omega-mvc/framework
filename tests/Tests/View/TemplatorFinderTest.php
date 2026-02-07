<?php

/**
 * Part of Omega - Tests\View Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\View;

use PHPUnit\Framework\Attributes\CoversClass;
use Omega\View\Exceptions\ViewFileNotFoundException;
use Omega\View\TemplatorFinder;
use PHPUnit\Framework\TestCase;
use Tests\FixturesPathTrait;

/**
 * Test suite for the TemplatorFinder component.
 *
 * Ensures that template files can be located correctly across registered paths
 * and extensions, validates path and extension management, cache behavior,
 * and proper exception handling when templates cannot be found.
 *
 * @category  Tests
 * @package   View
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(ViewFileNotFoundException::class)]
#[CoversClass(TemplatorFinder::class)]
final class TemplatorFinderTest extends TestCase
{
    use FixturesPathTrait;

    /**
     * Test it can find templator file location.
     *
     * @return void
     */
    public function testItCanFindTemplatorFileLocation(): void
    {
        $base = $this->fixturePath('/fixtures/view/sample/Templators');

        $view = new TemplatorFinder([$base], ['.php']);

        $this->assertEquals(
            $this->fixturePath('/fixtures/view/sample/Templators/php.php'),
            $view->find('php')
        );
    }

    /**
     * Test it can find templator file location will throw.
     *
     * @return void
     * @throws ViewFileNotFoundException If the template cannot be found in any registered path.
     */
    public function testItCanFindTemplatorFileLocationWillThrow(): void
    {
        $loader = $this->fixturePath('/fixtures/view/sampleTemplators');

        $view = new TemplatorFinder([$loader], ['.php']);

        $this->expectException(ViewFileNotFoundException::class);
        $view->find('blade');
    }

    /**
     * Test it can chack file exists.
     *
     * @return void
     */
    public function testItCanCheckFIleExist(): void
    {
        $loader = $this->fixturePath('/fixtures/view/sample/Templators');

        $view = new TemplatorFinder([$loader], ['.php', '.component.php']);

        $this->assertTrue($view->exists('php'));
        $this->assertTrue($view->exists('repeat'));
        $this->assertFalse($view->exists('index.blade'));
    }

    /**
     * Test it can find in path.
     *
     * @return void
     */
    public function testItCanFindInPath(): void
    {
        $loader = $this->fixturePath('/fixtures/view/sample/Templators');

        $view = new TemplatorFinder([$loader], ['.php']);

        $this->assertEquals(
            $this->fixturePath('/fixtures/view/sample/Templators/php.php'),
            (fn () => $this->{'findInPath'}('php', [$loader]))->call($view)
        );
    }

    /**
     * Test it can fnd in path will throw exception
     *
     * @return void
     * @throws ViewFileNotFoundException If the template cannot be found in any registered path.
     */
    public function testItCanFindInPathWillThrowException(): void
    {
        $loader = $this->fixturePath('Templators');

        $view = new TemplatorFinder([$loader], ['.php']);

        $this->expectException(ViewFileNotFoundException::class);
        (fn () => $this->{'findInPath'}('blade', [$loader]))->call($view);
    }

    /**
     * Test it can add path.
     *
     * @return void
     */
    public function testItCanAddPath(): void
    {
        $loader = $this->fixturePath('/fixtures/view/sample/Templators');

        $view = new TemplatorFinder([], ['.php']);
        $view->addPath($loader);

        $this->assertEquals($this->fixturePath('/fixtures/view/sample/Templators/php.php'), $view->find('php'));
    }

    /**
     * Test it can set path.
     *
     * @return void
     */
    public function testItCanSetPath(): void
    {
        $loader = $this->fixturePath('/fixtures/view/sampleTemplators');

        $view  = new TemplatorFinder([], ['.php']);
        $paths = (fn () => $this->{'paths'})->call($view);
        $this->assertEquals([], $paths);
        $view->setPaths([$loader]);
        $paths = (fn () => $this->{'paths'})->call($view);
        $this->assertEquals([$loader], $paths);
    }

    /**
     * Test it can not add multi path.
     *
     * @return void
     */
    public function testItCanNotAddMultiPath(): void
    {
        $loader = $this->fixturePath('/fixtures/view/sampleTemplators');

        $view = new TemplatorFinder([], ['.php']);
        $view->addPath($loader);
        $view->addPath($loader);
        $view->addPath($loader);

        $this->assertEquals([$loader], $view->getPaths());
    }

    /**
     * Test it can add extension.
     *
     * @return void
     */
    public function testItCanAddExtension(): void
    {
        $loader = $this->fixturePath('/fixtures/view/sample/Templators');

        $view = new TemplatorFinder([$loader]);
        $view->addExtension('.php');

        $this->assertEquals($this->fixturePath('/fixtures/view/sample/Templators/php.php'), $view->find('php'));
    }

    /**
     * Test it can flush.
     *
     * @return void
     */
    public function testItCanFlush(): void
    {
        $loader = $this->fixturePath('/fixtures/view/sample/Templators');

        $view = new TemplatorFinder([$loader], ['.php']);

        $view->find('php');
        $views = (fn () => $this->{'views'})->call($view);
        $this->assertCount(1, $views);
        $view->flush();
        $views = (fn () => $this->{'views'})->call($view);
        $this->assertCount(0, $views);
    }

    /**
     * Test it can get paths registered.
     *
     * @return void
     */
    public function testItCanGetPathsRegistered(): void
    {
        $loader = $this->fixturePath('/fixtures/view/sampleTemplators');

        $view = new TemplatorFinder([$loader], ['.php']);

        $this->assertEquals([$loader], $view->getPaths());
    }

    /**
     * Test it can get extensions registered.
     *
     * @return void
     */
    public function testItCanGetExtensionsRegistered(): void
    {
        $loader = $this->fixturePath('/fixtures/view/sampleTemplators');

        $view = new TemplatorFinder([$loader], ['.php']);

        $this->assertEquals(['.php'], $view->getExtensions());
    }
}
