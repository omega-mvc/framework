<?php

declare(strict_types=1);

namespace Tests\Cron;

use Omega\Application\Application;
use Omega\Cron\Log;
use Omega\Facade\AbstractFacade;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Console\Commands\Fixtures\ScriptedConnection;
use Tests\TestCase;

use function str_contains;

#[CoversClass(Log::class)]
final class LogTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app = new Application('/');
        AbstractFacade::setFacadeBase($this->app);
    }

    public function testInterpolatesAMessageIntoTheCronTable(): void
    {
        $connection = new ScriptedConnection();

        $this->app->set('database', fn (): ScriptedConnection => $connection);

        (new Log())->interpolate('Task executed', ['task_id' => 123]);

        $this->assertCount(1, $connection->queries);
        $this->assertTrue(str_contains($connection->queries[0], 'INSERT INTO cron'));
        $this->assertTrue(str_contains($connection->queries[0], '(message, context, date_create)'));
    }
}
