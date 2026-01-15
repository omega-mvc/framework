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

use Omega\Security\Exceptions\Argon2IdHashingNotSupportedException;

use const PASSWORD_ARGON2ID;

/**
 * Hashes values using the Argon2id algorithm, extending ArgonHasher to
 * provide Argon2id-specific behavior while allowing configuration of memory,
 * time, and threads for secure password hashing.
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
class Argon2IdHasher extends ArgonHasher implements HashInterface
{
    /**
     * {@inheritdoc}
     */
    public function make(string $value, array $options = []): string
    {
        $hash = @password_hash($value, PASSWORD_ARGON2ID, [
            'memory_cost' => $options['memory'] ?? $this->memory,
            'time_cost'   => $options['time'] ?? $this->time,
            'threads'     => $options['threads'] ?? $this->threads,
        ]);

        if (!is_string($hash)) {
            throw new Argon2IdHashingNotSupportedException(PASSWORD_ARGON2ID . ' hashing not supported.');
        }

        return $hash;
    }

    /**
     * {@inheritdoc}
     */
    public function isValidAlgorithm(string $hash): bool
    {
        return 'argon2id' === $this->info($hash)['algoName'];
    }
}
