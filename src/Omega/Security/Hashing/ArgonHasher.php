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

use function is_string;
use function password_hash;

use const PASSWORD_ARGON2I;

/**
 * Hashes values using the Argon2i algorithm, allowing configuration of
 * memory cost, time cost, and parallelism threads. Extends the default
 * hasher with Argon2i-specific behavior and validation.
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
class ArgonHasher extends DefaultHasher implements HashInterface
{
    /** @var int Memory cost in kibibytes used for Argon2i hashing. */
    protected int $memory = 1024;

    /** @var int Time cost (iterations) used for Argon2i hashing. */
    protected int $time = 2;

    /** @var int Number of threads (parallelism) used for Argon2i hashing. */
    protected int $threads = 2;

    /**
     * Set the memory cost for Argon2i hashing.
     *
     * @param int $memory Memory cost in kibibytes.
     * @return $this
     */
    public function setMemory(int $memory): self
    {
        $this->memory = $memory;

        return $this;
    }

    /**
     * Set the time cost (iterations) for Argon2i hashing.
     *
     * @param int $time Time cost in iterations.
     * @return $this
     */
    public function setTime(int $time): self
    {
        $this->time = $time;

        return $this;
    }

    /**
     * Set the number of threads (parallelism) for Argon2i hashing.
     *
     * @param int $threads Number of parallel threads.
     * @return $this
     */
    public function setThreads(int $threads): self
    {
        $this->threads = $threads;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function make(string $value, array $options = []): string
    {
        $hash = @password_hash($value, PASSWORD_ARGON2I, [
            'memory_cost' => $options['memory'] ?? $this->memory,
            'time_cost'   => $options['time'] ?? $this->time,
            'threads'     => $options['threads'] ?? $this->threads,
        ]);

        if (!is_string($hash)) {
            throw new Argon2IdHashingNotSupportedException(PASSWORD_ARGON2I . ' hashing not supported.');
        }

        return $hash;
    }

    /**
     * {@inheritdoc}
     */
    public function isValidAlgorithm(string $hash): bool
    {
        return 'argon2i' === $this->info($hash)['algoName'];
    }
}
