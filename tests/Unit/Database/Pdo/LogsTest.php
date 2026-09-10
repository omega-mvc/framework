<?php

declare(strict_types=1);

namespace Tests\Database\Pdo;

use Tests\Database\Asserts\UserTrait;
use Tests\Database\AbstractTestDatabase;

final class LogsTest extends AbstractTestDatabase
{
    use UserTrait;

    protected function setUp(): void
    {
        $this->createConnection();
        $this->createUserSchema();
        $this->createUser([
            [
                'user'     => 'taylor',
                'password' => 'secret',
                'stat'     => 99,
            ],
        ]);
    }

    protected function tearDown(): void
    {
        $this->dropConnection();
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanGetLogExcutionConnention(): void
    {
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
            $this->assertEquals($log['query'], $logs[$key]);
            $this->assertNotNull($log['duration']);
        }
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanSelectQuery(): void
    {
        $this->assertNotEmpty($this->pdo->getLogs());
        foreach ($this->pdo->getLogs() as $key => $log) {
            $this->assertArrayHasKey('query', $log);
            $this->assertArrayHasKey('started', $log);
            $this->assertArrayHasKey('ended', $log);
            $this->assertArrayHasKey('duration', $log);
        }
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanFlush(): void
    {
        $this->assertNotEmpty($this->pdo->getLogs());
        $this->pdo->flushLogs();
        $this->assertEmpty($this->pdo->getLogs());
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanEmptyLogsGetLogs(): void
    {
        $this->pdo->flushLogs();
        $this->assertEmpty($this->pdo->getLogs()); // Should not throw error
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanGetMultipleGetLogsCalls(): void
    {
        $this->pdo->flushLogs();
        $this->pdo->query('SELECT 1')->execute();

        $firstCall  = $this->pdo->getLogs();
        $secondCall = $this->pdo->getLogs();
        $thirdCall  = $this->pdo->getLogs();

        $this->assertEquals($firstCall, $secondCall);
        $this->assertEquals($secondCall, $thirdCall);
    }
}
