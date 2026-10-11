<?php

declare(strict_types=1);

namespace Tests\Database\Pdo;

use Exception;
use Omega\Database\AbstractConnection;
use Omega\Database\MysqlConnection;
use PDOException;
use PHPUnit\Framework\Attributes\TestWith;
use ReflectionClass;
use RuntimeException;
use Tests\TestCase;
use Throwable;

use function is_bool;
use function str_repeat;

final class LostConnectionTest extends TestCase
{
    #[TestWith(['MySQL server has gone away'])]
    #[TestWith(['Lost connection to MySQL server during query'])]
    #[TestWith(['server closed the connection unexpectedly'])]
    #[TestWith(['no connection to the server'])]
    #[TestWith(['Transaction() on null'])]
    #[TestWith(['SSL: Connection timed out'])]
    #[TestWith(['reset by peer'])]
    #[TestWith(['SQLSTATE[HY000] [2002] Connection refused'])]
    #[TestWith(['Physical connection is not usable'])]
    #[TestWith(['MYSQL SERVER HAS GONE AWAY'])]
    public function testItThrowExceptionCausedByLostConnectionReturnsTrue(string $errorMessage): void
    {
        $exception = new PDOException($errorMessage);

        $this->assertTrue(self::isLostConnection($exception));
    }

    #[TestWith(['Access denied for user \'root\'@\'localhost\''])]
    #[TestWith(['SQLSTATE[42000]: Syntax error or access violation'])]
    #[TestWith(['SQLSTATE[23000]: Integrity constraint violation'])]
    #[TestWith(['Table \'database.users\' doesn\'t exist'])]
    #[TestWith([''])]
    #[TestWith(['File not found'])]
    public function testItThrowExceptionCausedByLostConnectionReturnsFalse(string $errorMessage): void
    {
        $exception = new PDOException($errorMessage);

        $this->assertFalse(self::isLostConnection($exception));
    }

    public function testItThrowExceptionCausedByLostConnectionWithNonPdoException(): void
    {
        $exception = new Exception('Some generic error');

        $this->assertFalse(self::isLostConnection($exception));
    }

    public function testItThrowExceptionCausedByLostConnectionWithRuntimeException(): void
    {
        $exception = new RuntimeException('server has gone away');

        $this->assertTrue(self::isLostConnection($exception));
    }

    public function testItThrowExceptionCausedByLostConnectionWithLongMessage(): void
    {
        $longMessage = str_repeat('Some long error message ', 100) . 'MySQL server has gone away'
            . str_repeat(' with more details', 50);
        $exception   = new PDOException($longMessage);

        $this->assertTrue(self::isLostConnection($exception));
    }

    /**
     * Invoke the protected lost-connection matcher against a connection
     * instance created without opening a real database link.
     *
     * @param Throwable $exception Exception produced by a driver.
     * @return bool
     */
    private static function isLostConnection(Throwable $exception): bool
    {
        $reflection = new ReflectionClass(MysqlConnection::class);
        /** @var AbstractConnection $connection */
        $connection = $reflection->newInstanceWithoutConstructor();

        $method = $reflection->getMethod('causedByLostConnection');
        $result = $method->invoke($connection, $exception);

        if (!is_bool($result)) {
            throw new RuntimeException('causedByLostConnection() must return a boolean.');
        }

        return $result;
    }
}
