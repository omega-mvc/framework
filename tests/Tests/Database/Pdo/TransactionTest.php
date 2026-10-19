<?php

declare(strict_types=1);

namespace Tests\Database\Pdo;

use Omega\Database\ConnectionInterface;
use PDOException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Database\ManagesDatabase;
use Tests\TestCase;

final class TransactionTest extends TestCase
{
    use ManagesDatabase;

    protected function tearDown(): void
    {
        $this->dropConnection();

        parent::tearDown();
    }

    #[DataProvider('engineProvider')]
    public function testItCanRollbackTransaction(string $engine): void
    {
        $this->createConnection($engine);
        $this->createUserSchema();

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
           ->resultset() ?: [];

        $this->assertEquals(1, $user[0]['stat']);
    }

    #[DataProvider('engineProvider')]
    public function testItCanCommitTransaction(string $engine): void
    {
        $this->createConnection($engine);
        $this->createUserSchema();

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
           ->resultset() ?: [];

        $this->assertEquals(0, $user[0]['stat']);
    }

    #[DataProvider('engineProvider')]
    public function testItCanCommitTransactionUsingClosure(string $engine): void
    {
        $this->createConnection($engine);
        $this->createUserSchema();

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
           ->resultset() ?: [];

        $this->assertEquals(0, $user[0]['stat']);
        $this->assertTrue($transaction);
    }

    #[DataProvider('engineProvider')]
    public function testItCanCommitTransactionUsingClosureParameter(string $engine): void
    {
        $this->createConnection($engine);
        $this->createUserSchema();

        $this->pdo->query('INSERT INTO users (user, password, stat) VALUES (:user, :password, :stat)')
           ->bind(':user', 'test_user')
           ->bind(':password', 'test_password')
           ->bind(':stat', 1)
           ->execute();

        $test = function (ConnectionInterface $pdo): bool {
            $pdo->query('UPDATE users SET stat = :stat WHERE user = :user')
               ->bind(':stat', 0)
               ->bind(':user', 'test_user')
               ->execute();

            return true;
        };

        $transaction = $this->pdo->transaction($test);

        $user = $this->pdo->query('SELECT * FROM users WHERE user = :user')
           ->bind(':user', 'test_user')
           ->resultset() ?: [];

        $this->assertEquals(0, $user[0]['stat']);
        $this->assertTrue($transaction);
    }

    #[DataProvider('engineProvider')]
    public function testItCanRollbackTransactionUsingCloser(string $engine): void
    {
        $this->createConnection($engine);
        $this->createUserSchema();

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
           ->resultset() ?: [];

        $this->assertEquals(1, $user[0]['stat']);
        $this->assertFalse($transaction);
    }

    #[DataProvider('engineProvider')]
    public function testItCanRollbackTransactionUsingCloserWithThrow(string $engine): void
    {
        $this->createConnection($engine);
        $this->createUserSchema();

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

            throw new PDOException('Test Exception');
        };

        $transaction = $this->pdo->transaction($test);

        $user = $this->pdo->query('SELECT * FROM users WHERE user = :user')
           ->bind(':user', 'test_user')
           ->resultset() ?: [];

        $this->assertEquals(1, $user[0]['stat']);
        $this->assertFalse($transaction);
    }
}
