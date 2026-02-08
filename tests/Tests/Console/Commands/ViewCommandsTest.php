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

use Tests\FixturesPathTrait;
use function file_put_contents;
use function md5;
use function ob_get_clean;
use function ob_start;

/**
 * Class ViewCommandsTest
 *
 * This test class verifies the functionality of view-related console commands
 * in the framework, specifically focusing on caching and clearing compiled view
 * files. It ensures that the `ViewCommand` can correctly compile templates from
 * the application's view paths and store them in the cache directory, as well as
 * remove compiled files when requested.
 *
 * The tests simulate a minimal application environment using fixture paths and
 * validate the expected results by asserting exit codes and the existence of
 * compiled view files.
 *
 * @category   Tests
 * @package    Console
 * @subpackage Commands
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html GPL V3.0+
 * @version    2.0.0
 */
#[CoversClass(Application::class)]
#[CoversClass(ViewCommand::class)]
final class ViewCommandsTest extends TestCase
{
    use FixturesPathTrait;

    /**
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanCompileFromTemplatorFiles(): void
    {
        $app = new Application('');

        $app->set('path.cache', $this->setFixturePath(slash(path: '/fixtures/application-write/view_cache/')));
        $app->set('path.view', $this->setFixturePath(slash(path: '/fixtures/application-read/view/')));

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
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanClearCompiledViewFile(): void
    {
        $app = new Application('');
        $app->set('path.compiled_view_path', $this->setFixturePath(slash(path: '/fixtures/application-write/view_cache/')));

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
