<?php

declare(strict_types=1);

namespace Tests\Database\Pdo;

use Exception;
use Omega\Database\AbstractConnection;
use Omega\Database\MysqlConnection;
use PDOException;
use ReflectionClass;
use RuntimeException;
use Throwable;

use function is_bool;
use function str_repeat;

/**
 * Invoke the protected lost-connection matcher against a connection
 * instance created without opening a real database link.
 *
 * @param Throwable $exception Exception produced by a driver.
 * @return bool
 */
function isLostConnection(Throwable $exception): bool
{
    $reflection = new ReflectionClass(MysqlConnection::class);
    /** @var AbstractConnection $connection */
    $connection = $reflection->newInstanceWithoutConstructor();

    $method = $reflection->getMethod('causedByLostConnection');
    $method->setAccessible(true);
    $result = $method->invoke($connection, $exception);

    if (!is_bool($result)) {
        throw new RuntimeException('causedByLostConnection() must return a boolean.');
    }

    return $result;
}

test('it throw exception caused by lost connection returns true', function (string $errorMessage): void {
    $exception = new PDOException($errorMessage);

    expect(isLostConnection($exception))->toBeTrue();
})->with([
    // MySQL/MariaDB
    ['MySQL server has gone away'],
    ['Lost connection to MySQL server during query'],

    // PostgreSQL
    ['server closed the connection unexpectedly'],
    ['no connection to the server'],

    // SQLite
    ['Transaction() on null'],

    // SSL/Network
    ['SSL: Connection timed out'],
    ['reset by peer'],

    // PDO/PHP
    ['SQLSTATE[HY000] [2002] Connection refused'],

    // Generic
    ['Physical connection is not usable'],

    // Case sensitivity
    ['MYSQL SERVER HAS GONE AWAY'],
]);

test('it throw exception caused by lost connection returns false', function (string $errorMessage): void {
    $exception = new PDOException($errorMessage);

    expect(isLostConnection($exception))->toBeFalse();
})->with([
    // Authentication errors
    ['Access denied for user \'root\'@\'localhost\''],

    // SQL syntax errors
    ['SQLSTATE[42000]: Syntax error or access violation'],

    // Constraint violations
    ['SQLSTATE[23000]: Integrity constraint violation'],

    // Database/table not found
    ['Table \'database.users\' doesn\'t exist'],

    // Empty message
    [''],

    // Random non-database error
    ['File not found'],
]);

test('it throw exception caused by lost connection with non pdo exception', function (): void {
    $exception = new Exception('Some generic error');

    expect(isLostConnection($exception))->toBeFalse();
});

test('it throw exception caused by lost connection with runtime exception', function (): void {
    $exception = new RuntimeException('server has gone away');

    expect(isLostConnection($exception))->toBeTrue();
});

test('it throw exception caused by lost connection with long message', function (): void {
    $longMessage = str_repeat('Some long error message ', 100) . 'MySQL server has gone away'
        . str_repeat(' with more details', 50);
    $exception   = new PDOException($longMessage);

    expect(isLostConnection($exception))->toBeTrue();
});