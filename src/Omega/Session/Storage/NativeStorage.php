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

use Omega\Session\StorageInterface;

use function is_string;
use function bin2hex;
use function random_bytes;
use function session_destroy;
use function session_id;
use function session_name;
use function session_start;
use function session_status;
use function session_write_close;

use const PHP_SESSION_ACTIVE;

/**
 * Native PHP session storage driver.
 *
 * Wraps the built-in session handler backed by {@see $_SESSION}. The JSON
 * payload managed by {@see \Omega\Session\SessionManager} is stored under a
 * single namespaced key so that the manager can share the superglobal with
 * application code.
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
class NativeStorage implements StorageInterface
{
    /** @var array{prefix?: string, name?: string} */
    private array $options;

    private string $key;

    /** @param array{prefix?: string, name?: string} $options */
    public function __construct(array $options = [])
    {
        $this->options = $options;
        $this->key     = ($options['prefix'] ?? 'omega') . '_session';

        if (isset($options['name'])) {
            session_name($options['name']);
        }
    }

    public function read(string $id): string|false
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_id($id);
            session_start();
        }

        $data = $_SESSION[$this->key] ?? null;

        if (!is_string($data)) {
            return false;
        }

        if ($data === '') {
            return false;
        }

        return $data;
    }

    public function write(string $id, string $data): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_id($id);
            session_start();
        }

        $_SESSION[$this->key] = $data;

        return true;
    }

    public function destroy(string $id): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_id($id);
            session_start();
        }

        unset($_SESSION[$this->key]);

        session_destroy();

        return true;
    }

    public function gc(int $maxLifetime): int|false
    {
        return 0;
    }

    public function createId(): string
    {
        return bin2hex(random_bytes(16));
    }

    public function close(): bool
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        return true;
    }
}