<?php

declare(strict_types=1);

namespace Omega\Cron;

use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Cron\Log;
use Omega\Cron\Schedule;
use Omega\Container\AbstractServiceProvider;

use function Omega\Time\now;

class CronServiceProvider extends AbstractServiceProvider
{
    /**
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     */
    public function boot(): void
    {
        $this->app->set('cron.log',
            fn (): Log => new Log()
        );

        $this->app->set('schedule',
            fn (): Schedule => new Schedule(now()->getTimestamp(), $this->app->get('cron.log'))
        );
    }
}
