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

interface CsrfInterface
{
    public function generateToken(): string;

    public function validateToken(?string $token): bool;

    public function getToken(): string;

    public function getTokenField(): string;

    public function getTokenName(): string;
}
