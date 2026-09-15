<?php

/**
 * Part of Omega - Session Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Session\Storage;

use Omega\Database\ConnectionInterface;
use Omega\Session\StorageInterface;

use function bin2hex;
use function is_string;
use function random_bytes;
use function time;

/**
 * Database-backed session storage driver.
 *
 * Stores session rows in a relational table via {@see ConnectionInterface}.
 * The expected table schema:
 *
 * ```sql
 * CREATE TABLE sessions (
 *     id            VARCHAR(128) NOT NULL PRIMARY KEY,
 *     data          TEXT         NOT NULL,
 *     last_activity INT UNSIGNED NOT NULL
 * );
 * ```
 *
 * @category   Omega
 * @package    Session
 * @subpackage Storage
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class DatabaseStorage implements StorageInterface
{
    public function __construct(
        private ConnectionInterface $pdo,
        private string $table = 'sessions',
    ) {
    }

    public function read(string $id): string|false
    {
        $result = $this->pdo
            ->query("SELECT data FROM {$this->table} WHERE id = :id AND last_activity > :expiry")
            ->bind('id', $id)
            ->bind('expiry', time() - 7200)
            ->single();

        if ($result === false) {
            return false;
        }

        $data = $result['data'] ?? null;

        return is_string($data) && $data !== '' ? $data : false;
    }

    public function write(string $id, string $data): bool
    {
        $this->pdo
            ->query("DELETE FROM {$this->table} WHERE id = :id")
            ->bind('id', $id)
            ->execute();

        $this->pdo
            ->query("INSERT INTO {$this->table} (id, data, last_activity) VALUES (:id, :data, :last_activity)")
            ->bind('id', $id)
            ->bind('data', $data)
            ->bind('last_activity', time())
            ->execute();

        return true;
    }

    public function destroy(string $id): bool
    {
        return $this->pdo
            ->query("DELETE FROM {$this->table} WHERE id = :id")
            ->bind('id', $id)
            ->execute();
    }

    public function gc(int $maxLifetime): int|false
    {
        $this->pdo
            ->query("DELETE FROM {$this->table} WHERE last_activity < :expiry")
            ->bind('expiry', time() - $maxLifetime)
            ->execute();

        return $this->pdo->rowCount();
    }

    public function createId(): string
    {
        return bin2hex(random_bytes(16));
    }

    public function close(): bool
    {
        return true;
    }
}
