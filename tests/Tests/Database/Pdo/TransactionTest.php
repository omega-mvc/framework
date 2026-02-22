<?php

declare(strict_types=1);

namespace Tests\Database\Pdo;

use Tests\Database\AbstractTestDatabase;

final class TransactionTest extends AbstractTestDatabase
{
    protected function setUp(): void
    {
        $this->createConnection();
        $this->createUserSchema();
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
    public function testItCanRollbackTransaction()
    {
        $this->pdo->query('INSERT INTO users (user, password, stat) VALUES (:user, :password, :stat)')
           ->bind(':user', 'test_user')
           ->bind(':password', 'test_password')
           ->bind(':stat', 1)
           ->execute();
        $this->pdo->beginTransaction();
        $this->pdo->query('UPDATE users SET stat = :stat WHERE user = :user')
           ->bind(':stat', 0)
           ->bind(':user', 'test_user')
           ->execute();
        $this->pdo->cancelTransaction();
        $user = $this->pdo->query('SELECT * FROM users WHERE user = :user')
           ->bind(':user', 'test_user')
           ->resultset();
        $this->assertEquals(1, $user[0]['stat']);
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanCommitTransaction()
    {
        $this->pdo->query('INSERT INTO users (user, password, stat) VALUES (:user, :password, :stat)')
           ->bind(':user', 'test_user')
           ->bind(':password', 'test_password')
           ->bind(':stat', 1)
           ->execute();
        $this->pdo->beginTransaction();
        $this->pdo->query('UPDATE users SET stat = :stat WHERE user = :user')
           ->bind(':stat', 0)
           ->bind(':user', 'test_user')
           ->execute();
        $this->pdo->endTransaction();
        $user = $this->pdo->query('SELECT * FROM users WHERE user = :user')
           ->bind(':user', 'test_user')
           ->resultset();
        $this->assertEquals(0, $user[0]['stat']);
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanCommitTransactionUsingClosure(): void
    {
        $this->pdo->query('INSERT INTO users (user, password, stat) VALUES (:user, :password, :stat)')
           ->bind(':user', 'test_user')
           ->bind(':password', 'test_password')
           ->bind(':stat', 1)
           ->execute();

        $test = function (): bool {
            $this->pdo->query('UPDATE users SET stat = :stat WHERE user = :user')
               ->bind(':stat', 0)
               ->bind(':user', 'test_user')
               ->execute();

            return true;
        };

        $transaction = $this->pdo->transaction($test);

        $user = $this->pdo->query('SELECT * FROM users WHERE user = :user')
           ->bind(':user', 'test_user')
           ->resultset();
        $this->assertEquals(0, $user[0]['stat']);
        $this->assertTrue($transaction);
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanCommitTransactionUsingClosureParameter(): void
    {
        $this->pdo->query('INSERT INTO users (user, password, stat) VALUES (:user, :password, :stat)')
           ->bind(':user', 'test_user')
           ->bind(':password', 'test_password')
           ->bind(':stat', 1)
           ->execute();

        $test = function ($pdo): bool {
            $pdo->query('UPDATE users SET stat = :stat WHERE user = :user')
               ->bind(':stat', 0)
               ->bind(':user', 'test_user')
               ->execute();

            return true;
        };

        $transaction = $this->pdo->transaction($test);

        $user = $this->pdo->query('SELECT * FROM users WHERE user = :user')
           ->bind(':user', 'test_user')
           ->resultset();
        $this->assertEquals(0, $user[0]['stat']);
        $this->assertTrue($transaction);
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanRollbackTransactionUsingCloser(): void
    {
        $this->pdo->query('INSERT INTO users (user, password, stat) VALUES (:user, :password, :stat)')
           ->bind(':user', 'test_user')
           ->bind(':password', 'test_password')
           ->bind(':stat', 1)
           ->execute();

        $test = function (): bool {
            $this->pdo->query('UPDATE users SET stat = :stat WHERE user = :user')
               ->bind(':stat', 0)
               ->bind(':user', 'test_user')
               ->execute();
            $this->pdo->query('UPDATE users SET stat = :stat WHERE user = :user')
               ->bind(':stat', 2)
               ->bind(':user', 'test_user')
               ->execute();

            return false;
        };

        $transaction = $this->pdo->transaction($test);

        $user = $this->pdo->query('SELECT * FROM users WHERE user = :user')
           ->bind(':user', 'test_user')
           ->resultset();
        $this->assertEquals(1, $user[0]['stat']);
        $this->assertFalse($transaction);
    }

    /**
     * @test
     *
     * @group database
     */
    public function testItCanRollbackTransactionUsingCloserWithThrow(): void
    {
        $this->pdo->query('INSERT INTO users (user, password, stat) VALUES (:user, :password, :stat)')
           ->bind(':user', 'test_user')
           ->bind(':password', 'test_password')
           ->bind(':stat', 1)
           ->execute();

        $test = function (): bool {
            $this->pdo->query('UPDATE users SET stat = :stat WHERE user = :user')
               ->bind(':stat', 0)
               ->bind(':user', 'test_user')
               ->execute();

            throw new \PDOException('Test Exception');

            return true;
        };

        $transaction =  $this->pdo->transaction($test);

        $user = $this->pdo->query('SELECT * FROM users WHERE user = :user')
           ->bind(':user', 'test_user')
           ->resultset();
        $this->assertEquals(1, $user[0]['stat']);
        $this->assertFalse($transaction);
    }
}
