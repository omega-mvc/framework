<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Console\Commands\RouteCommand;
use Omega\Router\Router;

final class RouteCommandsTest extends AbstractTestCommand
{
    public function testItCanRenderRouteWithSomeRouter(): void
    {
        Router::get('/test', fn () => '');
        Router::post('/post', fn () => '');

        $route_command = new RouteCommand($this->argv('php route:list'));
        ob_start();
        $exit = $route_command->main();
        $out  = ob_get_clean();

        $this->assertSuccess($exit);
        $this->assertContain('GET', $out);
        $this->assertContain('/test', $out);

        Router::Reset();
    }
}
