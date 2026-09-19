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

use Omega\Cache\CacheInterface;
use Omega\Session\StorageInterface;

use function bin2hex;
use function is_string;
use function random_bytes;

/**
 * Cache-backed session storage driver.
 *
 * Persists session data using any {@see CacheInterface} implementation
 * (file, Redis, APCu, etc.). Session expiry is handled by the cache TTL.
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
class CacheStorage implements StorageInterface
{
    public function __construct(
        private CacheInterface $cache,
        private string $prefix = 'session_',
    ) {
    }

    public function read(string $id): string|false
    {
        $data = $this->cache->get($this->prefix . $id, '');

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
        return $this->cache->set($this->prefix . $id, $data, 7200);
    }

    public function destroy(string $id): bool
    {
        return $this->cache->delete($this->prefix . $id);
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
        return true;
    }
}
