<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Application\Application;
use Omega\Console\Commands\ViewCommand;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\View\Templator;
use Omega\View\TemplatorFinder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;

use function file_put_contents;
use function md5;
use function ob_get_clean;
use function ob_start;

#[CoversClass(Application::class)]
#[CoversClass(ViewCommand::class)]
final class ViewCommandsTest extends TestCase
{
    /**
     * @return void
     * @throws BindingResolutionException
     * @throws CircularAliasException
     * @throws EntryNotFoundException
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     */
    public function testItCanCompileFromTemplatorFiles(): void
    {
        $app = new Application('');

        $app->set('path.cache', slash(path: __DIR__ . '/fixtures/view_cache/'));
        $app->set('path.view', slash(path: __DIR__ . '/fixtures/view/'));

        $app->set(
            TemplatorFinder::class,
            fn () => new TemplatorFinder([get_path('path.view')], ['.php', ''])
        );

        $app->set(
            'view.instance',
            fn (TemplatorFinder $finder) => new Templator($finder, get_path('path.cache'))
        );

        $viewCommand = new ViewCommand(['php', 'omega', 'view:cache'], [
            'prefix' => '*.php',
        ]);
        ob_start();
        $exit = $viewCommand->cache($app->make(Templator::class));
        ob_get_clean();

        $this->assertEquals(0, $exit);
        $this->assertFileExists(get_path('path.cache') . md5('test.php') . '.php');
    }

    /**
     * Test it can clear compiled view file.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws CircularAliasException
     * @throws ContainerExceptionInterface
     * @throws EntryNotFoundException
     * @throws ReflectionException
     */
    public function testItCanClearCompiledViewFile(): void
    {
        $app = new Application('');
        $app->set('path.compiled_view_path', slash(path: __DIR__ . '/fixtures/view_cache/'));

        file_put_contents(get_path('path.compiled_view_path') . 'test01.php', '');
        file_put_contents(get_path('path.compiled_view_path') . 'test02.php', '');

        $viewCommand = new ViewCommand(['php', 'omega', 'view:clear'], [
            'prefix' => '*.php',
        ]);
        ob_start();
        $exit = $viewCommand->clear();
        ob_get_clean();
        $this->assertEquals(0, $exit);
    }
}
