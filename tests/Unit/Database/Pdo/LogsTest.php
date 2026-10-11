<?php

declare(strict_types=1);

namespace Tests\Database\Pdo;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Database\Asserts\UserTrait;
use Tests\Database\ManagesDatabase;
use Tests\TestCase;

final class LogsTest extends TestCase
{
    use ManagesDatabase;
    use UserTrait;

    protected function tearDown(): void
    {
        $this->dropConnection();

        parent::tearDown();
    }

    #[DataProvider('engineProvider')]
    public function testItCanGetLogExcutionConnention(string $engine): void
    {
        $this->createConnection($engine);
        $this->seedDefaultUser();
        $this->pdo->flushLogs();
        $this->pdo->query('select * from users where user = :user')->bind('user', 'taylor')->resultset();
        $this->pdo->query('select * from users where user = :user')->bind('user', 'taylor')->single();
        $this->pdo->query('delete from users where user = :user')->bind('user', 'taylor')->execute();

        $logs = [
            'select * from users where user = :user',
            'select * from users where user = :user',
            'delete from users where user = :user',
        ];

        // after calculate
        foreach ($this->pdo->getLogs() as $key => $log) {
            $this->assertEquals($logs[$key], $log['query']);
            $this->assertNotNull($log['duration']);
        }
    }

    #[DataProvider('engineProvider')]
    public function testItCanSelectQuery(string $engine): void
    {
        $this->createConnection($engine);
        $this->seedDefaultUser();

        $this->assertNotEmpty($this->pdo->getLogs());
        foreach ($this->pdo->getLogs() as $key => $log) {
            $this->assertArrayHasKey('query', $log);
            $this->assertArrayHasKey('started', $log);
            $this->assertArrayHasKey('ended', $log);
            $this->assertArrayHasKey('duration', $log);
        }
    }

    #[DataProvider('engineProvider')]
    public function testItCanFlush(string $engine): void
    {
        $this->createConnection($engine);
        $this->seedDefaultUser();

        $this->assertNotEmpty($this->pdo->getLogs());
        $this->pdo->flushLogs();
        $this->assertEmpty($this->pdo->getLogs());
    }

    #[DataProvider('engineProvider')]
    public function testItCanEmptyLogsGetLogs(string $engine): void
    {
        $this->createConnection($engine);
        $this->seedDefaultUser();
        $this->pdo->flushLogs();

        $this->assertEmpty($this->pdo->getLogs()); // Should not throw error
    }

    #[DataProvider('engineProvider')]
    public function testItCanGetMultipleGetLogsCalls(string $engine): void
    {
        $this->createConnection($engine);
        $this->seedDefaultUser();
        $this->pdo->flushLogs();
        $this->pdo->query('SELECT 1')->execute();

        $firstCall  = $this->pdo->getLogs();
        $secondCall = $this->pdo->getLogs();
        $thirdCall  = $this->pdo->getLogs();

        $this->assertEquals($firstCall, $secondCall);
        $this->assertEquals($secondCall, $thirdCall);
    }
}
