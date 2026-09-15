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

use function bin2hex;
use function random_bytes;

/**
 * In-memory array-based session storage for testing.
 *
 * Stores session data in a plain PHP array keyed by session ID.
 * Garbage collection is a no-op; all data lives for the lifetime of the process.
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
class ArrayStorage implements StorageInterface
{
    /** @var array<string, string> */
    private array $sessions = [];

    public function read(string $id): string|false
    {
        return $this->sessions[$id] ?? false;
    }

    public function write(string $id, string $data): bool
    {
        $this->sessions[$id] = $data;

        return true;
    }

    public function destroy(string $id): bool
    {
        unset($this->sessions[$id]);

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
        return true;
    }
}
