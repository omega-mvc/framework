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

use function password_hash;

use const PASSWORD_BCRYPT;

/**
 * Hashes values using the Bcrypt algorithm, allowing configuration of
 * the computational cost (rounds). Extends the default hasher with
 * Bcrypt-specific behavior and validation.
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
class BcryptHasher extends DefaultHasher implements HashInterface
{
    /** @var int The Bcrypt cost factor used when hashing values. */
    protected int $rounds = 12;

    /**
     * Set the Bcrypt cost factor (rounds).
     *
     * @param int $rounds The computational cost used when hashing.
     * @return $this
     */
    public function setRounds(int $rounds): self
    {
        $this->rounds = $rounds;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function make(string $value, array $options = []): string
    {
        return password_hash($value, PASSWORD_BCRYPT, [
            'cost' => $options['rounds'] ?? $this->rounds,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function isValidAlgorithm(string $hash): bool
    {
        return 'bcrypt' === $this->info($hash)['algoName'];
    }
}
