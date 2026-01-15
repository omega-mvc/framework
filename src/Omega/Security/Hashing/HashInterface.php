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

/**
 * Defines the contract for hashing services, including hash creation,
 * verification, algorithm validation, and metadata retrieval.
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
interface HashInterface
{
    /**
     * Retrieve metadata about the given hash, such as algorithm,
     * parameters, and status flags.
     *
     * @param string $hash The hash string to inspect.
     * @return array<string, int|string|bool> An associative array containing  hash information.
     */
    public function info(string $hash): array;

    /**
     * Verify that a plain value matches a previously generated hash.
     *
     * @param string $value                                The plain value to validate.
     * @param string                         $hashedValue  The existing hash to compare against.
     * @param array<string, int|string|bool> $options      Additional hashing options, such as algorithm settings.
     * @return bool True if the value matches the hash, false otherwise.
     */
    public function verify(string $value, string $hashedValue, array $options = []): bool;

    /**
     * Generate a hash from the provided value.
     *
     * @param string                         $value    The value to be hashed.
     * @param array<string, int|string|bool> $options Optional hashing parameters, such as cost or algorithm.
     * @return string The resulting hash.
     * @throws Argon2IdHashingNotSupportedException When hashing fails or an invalid configuration is provided.
     */
    public function make(string $value, array $options = []): string;

    /**
     * Determine whether the hash was created using a valid or supported algorithm.
     *
     * @param string $hash The hash string to validate.
     * @return bool True if the algorithm is supported, false otherwise.
     */
    public function isValidAlgorithm(string $hash): bool;
}
