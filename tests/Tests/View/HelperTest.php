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

use Exception;
use Omega\Application\Application;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Http\Response;
use Omega\Text\Str;
use Omega\View\Templator;
use Omega\View\TemplatorFinder;
use Omega\View\Vite;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;

use Tests\FixturesPathTrait;
use function Omega\View\view;
use function Omega\View\vite;

/**
 * Test suite for Omega global helper functions.
 *
 * This class verifies the behavior and consistency of all core helper
 * functions provided by the Omega\Application namespace.
 *
 * The tests cover:
 * - The `vite()` helper, validating both single and multiple entry point
 *   resolution using mocked dependencies.
 *
 * This suite ensures that helper functions behave consistently across
 * different input types (scalar vs array), maintain cross-platform
 * compatibility, and correctly integrate with the underlying application
 * infrastructure.
 *
 * @category  Tests
 * @package   View
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(Application::class)]
#[CoversFunction('Omega\View\view')]
#[CoversFunction('Omega\View\vite')]
final class HelperTest extends TestCase
{
    use FixturesPathTrait;

    /**
     * Test vite helper handles single and multiple entry points.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception Throw when a generic error occurred.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testViteHelperHandlesSingleAndMultipleEntryPoints(): void
    {
        $app = new Application(__DIR__);
        $viteMock = $this->createMock(Vite::class);
        $app->set('vite.gets', $viteMock);

        $viteMock->expects($this->exactly(2))
        ->method('gets')
            ->willReturnOnConsecutiveCalls(
                ['main.js' => 'url_string'],
                ['a.js' => 'url_a', 'b.js' => 'url_b']
            );

        $this->assertSame('url_string', vite('main.js'));

        $resultArray = vite('a.js', 'b.js');
        $this->assertIsArray($resultArray);
        $this->assertCount(2, $resultArray);
        $this->assertSame('url_a', $resultArray['a.js']);
    }

    /**
     * Test it can get response from container.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception Thrown when a generic error occurred.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanGetResponseFromContainer(): void
    {
        $app = new Application($this->setFixtureBasePath());

        $app->set(
            TemplatorFinder::class,
            fn () => new TemplatorFinder([$this->setFixturePath('/fixtures/support/view')], ['.php'])
        );

        $app->set(
            'view.instance',
            fn (TemplatorFinder $finder) => new Templator($finder, $this->setFixturePath('/fixtures/support/cache'))
        );

        $app->set(
            'view.response',
            fn () => fn (string $viewPath, array $portal = []): Response => new Response(
                $app->make(Templator::class)->render($viewPath, $portal)
            )
        );

        $view = view('test', [], ['status' => 500]);
        $this->assertEquals(500, $view->getStatusCode());
        $this->assertTrue(
            Str::contains($view->getContent(), 'omega')
        );

        $app->flush();
    }
}
