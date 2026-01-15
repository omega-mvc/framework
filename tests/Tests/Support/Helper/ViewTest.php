<?php

declare(strict_types=1);

namespace Tests\Support\Helper;

use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Omega\Application\Application;
use Omega\Http\Response;
use Omega\Text\Str;
use Omega\View\Templator;
use Omega\View\TemplatorFinder;
use ReflectionException;

#[CoversClass(Application::class)]
#[CoversClass(BindingResolutionException::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(EntryNotFoundException::class)]
#[CoversClass(Response::class)]
#[CoversClass(Str::class)]
#[CoversClass(Templator::class)]
#[CoversClass(TemplatorFinder::class)]
final class ViewTest extends TestCase
{
    /**
     * Test it can get response from container.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanGetResponseFromContainer(): void
    {
        $app = new Application(__DIR__);

        $app->set(
            TemplatorFinder::class,
            fn () => new TemplatorFinder([__DIR__ . '/fixtures/view'], ['.php'])
        );

        $app->set(
            'view.instance',
            fn (TemplatorFinder $finder) => new Templator($finder, __DIR__ . '/fixtures/cache')
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
