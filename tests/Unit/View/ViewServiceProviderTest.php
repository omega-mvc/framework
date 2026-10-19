<?php

declare(strict_types=1);

namespace Tests\View;

use Omega\Application\Application;
use Omega\Config\ConfigRepository;
use Omega\Http\Response;
use Omega\View\Exceptions\DirectiveNotRegisterException;
use Omega\View\Templator;
use Omega\View\Templator\DirectiveTemplator;
use Omega\View\TemplatorFinder;
use Omega\View\ViewServiceProvider;
use Omega\View\Vite;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

use function Omega\Application\slash;

#[CoversClass(ViewServiceProvider::class)]
final class ViewServiceProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $basePath = slash(__DIR__ . '/fixtures/support/');

        $this->app = new Application($basePath);
        $this->app->set('config', fn (): ConfigRepository => new ConfigRepository([
            'VIEW_EXTENSIONS' => ['.php'],
        ]));

        $this->app->set('path.public', $basePath . 'manifest/public');
        $this->app->set('paths.view', [$basePath . 'view']);
        $this->app->set('path.compiled_view_path', $basePath . 'cache');
    }

    protected function tearDown(): void
    {
        DirectiveTemplator::reset();
        $this->app->flush();

        parent::tearDown();
    }

    public function testBootsTheViteResolverBindings(): void
    {
        (new ViewServiceProvider($this->app))->boot();

        $this->assertInstanceOf(Vite::class, $this->app->get('vite.gets'));
        $this->assertIsString($this->app->get('vite.location'));
        $this->assertTrue($this->app->get('vite.hasManifest'));
    }

    public function testBootsTheViewResolverBindings(): void
    {
        (new ViewServiceProvider($this->app))->boot();

        $this->assertInstanceOf(TemplatorFinder::class, $this->app->get(TemplatorFinder::class));
        $this->assertInstanceOf(Templator::class, $this->app->get('view.instance'));

        $resolver = $this->app->get('view.response');
        $this->assertIsCallable($resolver);

        $response = $resolver('test');
        $this->assertInstanceOf(Response::class, $response);
        $this->assertStringContainsString('omega', $response->getContent());
    }

    public function testRegistersTheViteTemplateDirective(): void
    {
        (new ViewServiceProvider($this->app))->boot();

        $output = DirectiveTemplator::call('vite', ['resources/css/app.css']);

        $this->assertStringContainsString('fixtures/app-4ed993c7.css', $output);
    }

    public function testSkipsTheViteDirectiveWhenTheViteResolverIsNotRegistered(): void
    {
        $provider = new class ($this->app) extends ViewServiceProvider {
            public function registerDirectives(): void
            {
                $this->registerViteDirectives();
            }
        };

        $provider->registerDirectives();

        $this->expectException(DirectiveNotRegisterException::class);

        DirectiveTemplator::call('vite', ['resources/css/app.css']);
    }
}
