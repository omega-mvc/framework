<?php

/**
 * Part of Omega - Tests\Support Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Support\Helper;

use Exception;
use Omega\Application\Application;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Http\Response;
use Omega\Text\Str;
use Omega\View\Templator;
use Omega\View\TemplatorFinder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use Tests\FixturesPathTrait;

use function Omega\Support\view;

/**
 * Integration test suite for the `view()` helper.
 *
 * This class verifies that the view helper correctly interacts with the
 * application container and the templating system to produce a valid
 * HTTP response.
 *
 * The tests cover:
 * - Resolution of the view response factory from the container.
 * - Proper wiring of the templating engine and template finder.
 * - Rendering of templates using the configured templator.
 * - Wrapping rendered output into a Response instance.
 * - Handling of response options such as HTTP status codes.
 *
 * This is not a pure unit test of the helper itself, but an integration
 * test ensuring that all involved components (container, templator,
 * and response factory) work together as expected.
 *
 * @category   Tests
 * @package    Support
 * @subpackage Helper
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[CoversClass(Application::class)]
#[CoversClass(BindingResolutionException::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(EntryNotFoundException::class)]
#[CoversClass(Response::class)]
#[CoversClass(Str::class)]
#[CoversClass(Templator::class)]
#[CoversClass(TemplatorFinder::class)]
#[CoversFunction('Omega\Support\view')]
final class ViewTest extends TestCase
{
    use FixturesPathTrait;

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
