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

namespace Omega\Security;

use Omega\Security\Exceptions\InvalidCipherDefinitionException;
use Random\RandomException;

use function base64_decode;
use function base64_encode;
use function count;
use function explode;
use function hash;
use function openssl_decrypt;
use function openssl_encrypt;
use function random_bytes;

use const OPENSSL_RAW_DATA;

/**
 * Provides symmetric encryption and decryption capabilities using OpenSSL.
 *
 * This class acts as a lightweight wrapper around OpenSSL, handling key hashing,
 * IV generation, cipher parsing, and safe encoding/decoding of encrypted payloads.
 * It supports configurable algorithms (e.g., "AES-256-CBC;16") and optional
 * per-call pass phrase overrides for added flexibility.
 *
 * A secure random initialization vector (IV) is automatically generated upon
 * instantiation, and pass phrases are normalized using SHA-256 hashing to produce
 * fixed-length cryptographic keys.
 *
 * @category  Omega
 * @package   Security
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class Crypt
{
    /** @var string The OpenSSL cipher algorithm name (e.g., "AES-256-CBC"). */
    private string $cipherAlgo;

    /** @var string Initialization vector (IV) generated for the cipher, with length defined by the algorithm. */
    private string $iv;

    /** @var string The base key derived from the primary pass phrase using SHA-256 hashing. */
    private string $hash;

    /**
     * Create a new Crypt instance with the given pass phrase and cipher algorithm.
     *
     * The cipher algorithm must be provided in the format "ALGO_NAME;IV_LENGTH".
     * A secure random initialization vector (IV) is automatically generated based
     * on the specified length.
     *
     * @param string $passPhrase  The pass phrase used to generate the encryption key.
     * @param string $cipherAlgo  The cipher algorithm definition (e.g., "AES-256-CBC;16").
     * @throws RandomException If initialization vector generation fails.
     * @throws InvalidCipherDefinitionException If the cipher definition is invalid or malformed.
     */
    public function __construct(string $passPhrase, string $cipherAlgo)
    {
        [$this->cipherAlgo, $chars] = $this->algoParse($cipherAlgo);
        $this->iv                   = random_bytes($chars);
        $this->hash                 = $this->hash($passPhrase);
    }

    /**
     * Parse the cipher algorithm definition.
     *
     * The input must contain at least the algorithm name and IV length,
     * separated by a semicolon. For example: "AES-256-CBC;16".
     *
     * @param string $cipherAlgo  The cipher definition string.
     * @return array{string,int}  An array containing the algorithm name and IV length.
     * @throws InvalidCipherDefinitionException If the cipher definition is invalid or malformed.
     */
    private function algoParse(string $cipherAlgo): array
    {
        $parse = explode(';', $cipherAlgo);

        if (count($parse) < 2) {
            throw new InvalidCipherDefinitionException('Cipher algo must provide chars length');
        }

        return [$parse[0], (int) $parse[1]];
    }

    /**
     * Generate an SHA-256 hash of the provided pass phrase.
     *
     * The resulting 32-byte binary hash is suitable as an OpenSSL encryption key.
     *
     * @param string $passPhrase  The pass phrase to hash.
     * @return string The raw binary hash.
     */
    public function hash(string $passPhrase): string
    {
        return hash('sha256', $passPhrase, true);
    }

    /**
     * Encrypt the given plaintext using the configured algorithm and IV.
     *
     * A custom pass phrase may optionally be provided for this specific call.
     * If omitted, the constructor-derived key is used.
     *
     * The output is Base64-encoded for storage or transmission.
     *
     * @param string      $plainText   The plaintext string to encrypt.
     * @param string|null $passPhrase  Optional override pass phrase for encryption.
     * @return string The Base64-encoded encrypted string.
     */
    public function encrypt(string $plainText, ?string $passPhrase = null): string
    {
        $hash = $passPhrase === null ? null : $this->hash($passPhrase);

        return base64_encode(
            openssl_encrypt(
                $plainText,
                $this->cipherAlgo,
                $hash ?? $this->hash,
                OPENSSL_RAW_DATA,
                $this->iv
            )
        );
    }

    /**
     * Decrypt an encrypted Base64-encoded string using the configured algorithm and IV.
     *
     * A custom pass phrase may optionally be provided for this specific call.
     * If omitted, the constructor-derived key is used.
     *
     * @param string      $encrypted   The Base64-encoded encrypted string.
     * @param string|null $passPhrase  Optional override pass phrase for decryption.
     * @return string The decrypted plaintext string.
     */
    public function decrypt(string $encrypted, ?string $passPhrase = null): string
    {
        $hash = $passPhrase === null ? null : $this->hash($passPhrase);

        return openssl_decrypt(
            base64_decode($encrypted),
            $this->cipherAlgo,
            $hash ?? $this->hash,
            OPENSSL_RAW_DATA,
            $this->iv
        );
    }
}
