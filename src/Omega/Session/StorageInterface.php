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

namespace Omega\Session;

/**
 * Low-level session persistence contract.
 *
 * Implementations handle raw session data storage using a specific backend
 * (native PHP sessions, cache, database, etc.). The {@see SessionManager}
 * encodes/decodes JSON on top of these raw string operations.
 *
 * @category  Omega
 * @package   Session
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
interface StorageInterface
{
    public function read(string $id): string|false;

    public function write(string $id, string $data): bool;

    public function destroy(string $id): bool;

    public function gc(int $maxLifetime): int|false;

    public function createId(): string;

    public function close(): bool;
}
