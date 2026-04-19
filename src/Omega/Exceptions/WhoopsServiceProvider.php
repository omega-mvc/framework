<?php

declare(strict_types=1);

namespace Omega\Exceptions;

use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Container\AbstractServiceProvider;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

class WhoopsServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     *
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function boot(): void
    {
        if ($this->app->isDebugMode() && class_exists(Run::class)) {
            $this->app->set('error.handle', fn () => new Run());
            $this->app->set('error.PrettyPageHandler', fn () => new PrettyPageHandler());
            $this->app->set('error.PlainTextHandler', fn () => new PlainTextHandler());
        }
    }
}
