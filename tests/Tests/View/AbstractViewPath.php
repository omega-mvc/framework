<?php

declare(strict_types=1);

namespace Tests\View;

use Omega\View\Templator;
use Omega\View\TemplatorFinder;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Tests\FixturesPathTrait;

#[CoversNothing]
abstract class AbstractViewPath extends TestCase
{
    use FixturesPathTrait;

    protected string $path;
    protected string $cache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->path  = $this->fixturePath('/fixtures/view');
        $this->cache = $this->fixturePath('/fixtures/view');
    }

    /**
     * Returns a Templator instance.
     *
     * @param string $subPath Path relative to fixtures/view for templates. Default 'templator'.
     * @param string $cacheSubPath Path relative to fixtures/view for cache. Default 'templator'.
     * @return Templator
     */
    protected function getTemplator(string $subPath = 'templator', string $cacheSubPath = 'templator'): Templator
    {
        return new Templator(
            new TemplatorFinder([$this->viewPath($subPath)], ['']),
            $this->viewCache($cacheSubPath)
        );
    }

    protected function viewPath(string $subPath = ''): string
    {
        return $this->path . ($subPath !== '' ? slash('/' . ltrim($subPath, '/')) : '');
    }

    protected function viewCache(string $subPath = ''): string
    {
        return $this->cache . ($subPath !== '' ? slash('/' . ltrim($subPath, '/')) : '');
    }
}
