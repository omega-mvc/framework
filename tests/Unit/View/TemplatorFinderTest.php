<?php

declare(strict_types=1);

namespace Tests\View;

use Omega\View\Exceptions\ViewFileNotFoundException;
use Omega\View\TemplatorFinder;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(ViewFileNotFoundException::class)]
#[CoversClass(TemplatorFinder::class)]
final class TemplatorFinderTest extends TestCase
{
    public function testCanFindTemplatorFileLocation(): void
    {
        $base = __DIR__ . '/fixtures/view/sample/Templators';

        $view = new TemplatorFinder([$base], ['.php']);

        $this->assertEquals(
            __DIR__ . '/fixtures/view/sample/Templators/php.php',
            $view->find('php')
        );
    }

    public function testCanFindTemplatorFileLocationWillThrow(): void
    {
        $loader = __DIR__ . '/fixtures/view/sampleTemplators';

        $view = new TemplatorFinder([$loader], ['.php']);

        $this->expectException(ViewFileNotFoundException::class);
        $view->find('blade');
    }

    public function testCanFindInPath(): void
    {
        $loader = __DIR__ . '/fixtures/view/sample/Templators';

        $view = new TemplatorFinder([$loader], ['.php']);

        $this->assertEquals(
            __DIR__ . '/fixtures/view/sample/Templators/php.php',
            (fn () => $this->{'findInPath'}('php', [$loader]))->call($view)
        );
    }

    public function testCanFindInPathWillThrowException(): void
    {
        $loader = __DIR__ . '/fixtures/view/Templators';

        $view = new TemplatorFinder([$loader], ['.php']);

        $this->expectException(ViewFileNotFoundException::class);
        (fn () => $this->{'findInPath'}('blade', [$loader]))->call($view);
    }

    public function testFindInPathWithEmptyPathsThrows(): void
    {
        $view = new TemplatorFinder([], ['.php']);

        $this->expectException(ViewFileNotFoundException::class);

        (fn () => $this->{'findInPath'}('php', []))->call($view);
    }

    public function testFindInPathWithNoExtensionsThrows(): void
    {
        $loader = __DIR__ . '/fixtures/view/sample/Templators';

        $view = new TemplatorFinder([$loader], []);

        $this->expectException(ViewFileNotFoundException::class);

        (fn () => $this->{'findInPath'}('php', [$loader]))->call($view);
    }

    public function testCanAddPath(): void
    {
        $loader = __DIR__ . '/fixtures/view/sample/Templators';

        $view = new TemplatorFinder([], ['.php']);
        $view->addPath($loader);

        $this->assertEquals(
            __DIR__ . '/fixtures/view/sample/Templators/php.php',
            $view->find('php')
        );
    }

    public function testCanSetPath(): void
    {
        $loader = __DIR__ . '/fixtures/view/sampleTemplators';

        $view  = new TemplatorFinder([], ['.php']);
        $paths = (fn () => $this->{'paths'})->call($view);
        $this->assertEquals([], $paths);
        $view->setPaths([$loader]);
        $paths = (fn () => $this->{'paths'})->call($view);
        $this->assertEquals([$loader], $paths);
    }

    public function testSetPathsCoversForeachWithEmptyElement(): void
    {
        $view = new TemplatorFinder([], ['.php']);

        $dummyPath = __DIR__;
        $view->setPaths([$dummyPath]);

        $paths = (fn () => $this->{'paths'})->call($view);
        $this->assertEquals([realpath($dummyPath)], $paths);
    }

    public function testCanNotAddMultiPath(): void
    {
        $loader = __DIR__ . '/fixtures/view/sampleTemplators';

        $view = new TemplatorFinder([], ['.php']);
        $view->addPath($loader);
        $view->addPath($loader);
        $view->addPath($loader);

        $this->assertEquals([$loader], $view->getPaths());
    }

    public function testCanAddExtension(): void
    {
        $loader = __DIR__ . '/fixtures/view/sample/Templators';

        $view = new TemplatorFinder([$loader]);
        $view->addExtension('.php');

        $this->assertEquals(
            __DIR__ . '/fixtures/view/sample/Templators/php.php',
            $view->find('php')
        );
    }

    public function testCanFlush(): void
    {
        $loader = __DIR__ . '/fixtures/view/sample/Templators';

        $view = new TemplatorFinder([$loader], ['.php']);

        $view->find('php');
        /** @var array<string, string> $views */
        $views = (fn () => $this->{'views'})->call($view);
        $this->assertCount(1, $views);
        $view->flush();
        /** @var array<string, string> $views */
        $views = (fn () => $this->{'views'})->call($view);
        $this->assertCount(0, $views);
    }

    public function testCanGetPathsRegistered(): void
    {
        $loader = __DIR__ . '/fixtures/view/sampleTemplators';

        $view = new TemplatorFinder([$loader], ['.php']);

        $this->assertEquals([$loader], $view->getPaths());
    }

    public function testCanGetExtensionsRegistered(): void
    {
        $loader = __DIR__ . '/fixtures/view/sampleTemplators';

        $view = new TemplatorFinder([$loader], ['.php']);

        $this->assertEquals(['.php'], $view->getExtensions());
    }
}
