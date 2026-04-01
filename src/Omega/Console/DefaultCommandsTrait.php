<?php

declare(strict_types=1);

namespace Omega\Console;

use Omega\Console\Commands\CacheClearCommand;
use Omega\Console\Commands\ConfigCacheCommand;
use Omega\Console\Commands\ConfigClearCommand;
use Omega\Console\Commands\CronCommand;
use Omega\Console\Commands\CronListCommand;
use Omega\Console\Commands\CronWorkCommand;
use Omega\Console\Commands\DatabaseCreateCommand;
use Omega\Console\Commands\DatabaseDropCommand;
use Omega\Console\Commands\DatabaseShowCommand;
use Omega\Console\Commands\MaintenanceDownCommand;
use Omega\Console\Commands\MaintenanceUpCommand;
use Omega\Console\Commands\MakeCommand;
use Omega\Console\Commands\MakeControllerCommand;
use Omega\Console\Commands\MakeExceptionCommand;
use Omega\Console\Commands\MakeMiddlewareCommand;
use Omega\Console\Commands\MakeMigrationCommand;
use Omega\Console\Commands\MakeModelCommand;
use Omega\Console\Commands\MakeProviderCommand;
use Omega\Console\Commands\MakeSeedCommand;
use Omega\Console\Commands\MakeViewCommand;
use Omega\Console\Commands\MigrateCommand;
use Omega\Console\Commands\MigrateFreshCommand;
use Omega\Console\Commands\MigrateInitCommand;
use Omega\Console\Commands\MigrateRefreshCommand;
use Omega\Console\Commands\MigrateResetCommand;
use Omega\Console\Commands\MigrateRollbackCommand;
use Omega\Console\Commands\MigrateStatusCommand;
use Omega\Console\Commands\PackageDiscoverCommand;
use Omega\Console\Commands\RouteCacheCommand;
use Omega\Console\Commands\RouteClearCommand;
use Omega\Console\Commands\RouteCommand;
use Omega\Console\Commands\SeedCommand;
use Omega\Console\Commands\ServeCommand;
use Omega\Console\Commands\VendorPublishCommand;
use Omega\Console\Commands\ViewCacheCommand;
use Omega\Console\Commands\ViewClearCommand;
use Omega\Console\Commands\ViewWatchCommand;

trait DefaultCommandsTrait
{
    protected array $defaultCommands = [
        'cache:clear'      => CacheClearCommand::class,
        'config:cache'     => ConfigCacheCommand::class,
        'config:clear'     => ConfigClearCommand::class,
        'cron:run'         => CronCommand::class,
        'cron:list'        => CronListCommand::class,
        'cron:work'        => CronWorkCommand::class,
        'database:create'  => DatabaseCreateCommand::class,
        'database:drop'    => DatabaseDropCommand::class,
        'database:show'    => DatabaseShowCommand::class,
        'db:seed'          => SeedCommand::class,
        'db:make'          => MakeSeedCommand::class,
        'down'             => MaintenanceDownCommand::class,
        'make:command'     => MakeCommand::class,
        'make:controller'  => MakeControllerCommand::class,
        'make:exception'   => MakeExceptionCommand::class,
        'make:middleware'  => MakeMiddlewareCommand::class,
        'make:migration'   => MakeMigrationCommand::class,
        'make:model'       => MakeModelCommand::class,
        'make:provider'    => MakeProviderCommand::class,
        'make:view'        => MakeViewCommand::class,
        'migrate'          => MigrateCommand::class,
        'migrate:fresh'    => MigrateFreshCommand::class,
        'migrate:init'     => MigrateInitCommand::class,
        'migrate:refresh'  => MigrateRefreshCommand::class,
        'migrate:reset'    => MigrateResetCommand::class,
        'migrate:rollback' => MigrateRollbackCommand::class,
        'migrate:status'   => MigrateStatusCommand::class,
        'package:discover' => PackageDiscoverCommand::class,
        'route:cache'      => RouteCacheCommand::class,
        'route:clear'      => RouteClearCommand::class,
        'route:list'       => RouteCommand::class,
        'serve'            => ServeCommand::class,
        'up'               => MaintenanceUpCommand::class,
        'vendor:publish'   => VendorPublishCommand::class,
        'view:cache'       => ViewCacheCommand::class,
        'view:clear'       => ViewClearCommand::class,
        'view:watch'       => ViewWatchCommand::class,
    ];
}
