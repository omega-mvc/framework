<?php

declare(strict_types=1);

namespace Omega\View;

use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Http\Response;
use Omega\Container\AbstractServiceProvider;
use Omega\View\Templator\DirectiveTemplator;
use Closure;
use Omega\View\Vite;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;

use function array_filter;
use function array_merge;
use function array_values;
use function file_exists;
use function is_array;
use function is_string;
use function Omega\Application\get_path;

class ViewServiceProvider extends AbstractServiceProvider
{
    /**
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function boot(): void
    {
        $this->registerViteResolver();
        $this->registerViewResolver();
        $this->registerViteDirectives();
    }

    /**
     * @return void
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     */
    protected function registerViteResolver(): void
    {
        $publicPath = get_path('path.public');
        $publicPath = is_string($publicPath) ? $publicPath : '';

        $this->app->set('vite.gets', fn (): Vite => new Vite($publicPath, '/build/'));
        $this->app->set('vite.location', fn (): string => $publicPath . '/build/manifest.json');
        $this->app->set('vite.hasManifest', fn (): bool => file_exists($publicPath . '/build/manifest.json'));
    }

    /**
     * @return void
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     */
    protected function registerViteDirectives(): void
    {
        if ($this->app->has('vite.gets')) {
            /** @var Vite $vite */
            $vite = $this->app->get('vite.gets');

            DirectiveTemplator::register('vite', function (array $attributes) use ($vite): string {
                /** @var string[] $attributes */
                return $vite(...$attributes);
            });
        }
    }

    /**
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    protected function registerViewResolver(): void
    {
        /** @var Vite $vite */
        $vite = $this->app->get('vite.gets');

        /** @var array<string, mixed> $config */
        $config = $this->app->get('config');

        $globalTemplateVar = [
            'vite_has_manifest' => $this->app->get('vite.hasManifest'),
            'vite_hmr_script'   => $vite->isRunningHRM() ? $vite->getHmrScript() : '',
        ];

        $extensions  = $config['VIEW_EXTENSIONS'] ?? [];
        $extensions  = is_array($extensions) ? array_values(array_filter($extensions, 'is_string')) : [];
        $viewPaths   = get_path('paths.view');
        $viewPaths   = is_array($viewPaths) ? array_values($viewPaths) : [];
        $cachePath   = get_path('path.compiled_view_path');
        $cachePath   = is_string($cachePath) ? $cachePath : '';

        $this->app->set(TemplatorFinder::class, fn () => new TemplatorFinder($viewPaths, $extensions));
        $this->app->set(
            'view.instance',
            function () use ($cachePath): Templator {
                /** @var TemplatorFinder $finder */
                $finder = $this->app->get(TemplatorFinder::class);

                return new Templator($finder, $cachePath);
            }
        );
        $this->app->set(
            'view.response',
            function () use ($globalTemplateVar): Closure {
                return function (string $view, array $data = []) use ($globalTemplateVar): Response {
                    /** @var array<string, mixed> $data */
                    /** @var Templator $instance */
                    $instance = $this->app->get('view.instance');

                    return new Response(
                        $instance->render($view, array_merge($data, $globalTemplateVar))
                    );
                };
            }
        );
    }
}
