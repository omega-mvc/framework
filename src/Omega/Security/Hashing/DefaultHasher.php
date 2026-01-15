<?php

/**
 * Part of Omega - Security Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Security\Hashing;

use function password_get_info;
use function password_hash;
use function password_verify;

use const PASSWORD_DEFAULT;

/**
 * Provides a default hashing implementation using PHP's native
 * password_* functions. Supports hashing, verification, and
 * algorithm inspection through a simple and consistent API.
 *
 * @category   Omega
 * @package    Security
 * @subpackage Hashing
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class DefaultHasher implements HashInterface
{
    /**
     * {@inheritdoc}
     */
    public function info(string $hash): array
    {
        return password_get_info($hash);
    }

    /**
     * {@inheritdoc}
     */
    public function verify(string $value, string $hashedValue, array $options = []): bool
    {
        return password_verify($value, $hashedValue);
    }

    /**
     * {@inheritdoc}
     */
    public function make(string $value, array $options = []): string
    {
        return password_hash($value, PASSWORD_DEFAULT);
    }

    /**
     * {@inheritdoc}
     */
    public function isValidAlgorithm(string $hash): bool
    {
        return 'bcrypt' === $this->info($hash)['algoName'];
    }
}
