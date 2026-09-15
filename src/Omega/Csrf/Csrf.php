<?php

/**
 * Part of Omega - Csrf Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Csrf;

use Omega\Session\SessionManager;

use function bin2hex;
use function hash_equals;
use function is_string;
use function random_bytes;

class Csrf implements CsrfInterface
{
    public function __construct(private SessionManager $session)
    {
    }

    public function generateToken(): string
    {
        $token = bin2hex(random_bytes(32));

        $this->session->put($this->getTokenName(), $token);

        return $token;
    }

    public function validateToken(?string $token): bool
    {
        if ($token === null || $token === '') {
            return false;
        }

        $stored = $this->session->get($this->getTokenName());

        if (!is_string($stored) || $stored === '') {
            return false;
        }

        return hash_equals($stored, $token);
    }

    public function getToken(): string
    {
        $token = $this->session->get($this->getTokenName());

        if (!is_string($token) || $token === '') {
            $token = $this->generateToken();
        }

        return $token;
    }

    public function getTokenField(): string
    {
        return '_csrf_token';
    }

    public function getTokenName(): string
    {
        return '_csrf.token';
    }
}
