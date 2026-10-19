<?php

/**
 * Part of Omega - Tests\Container Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Container\Support;

use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Class requiring a scalar constructor argument with a default value.
 *
 * Used to test container behavior when a dependency cannot be auto-resolved
 * and the container falls back to the parameter default.
 *
 * @category   Tests
 * @package    Container
 * @subpackage Support
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[CoversNothing]
final class DefaultValueConstructorClass
{
    /**
     * @param int $count Scalar dependency with a fallback default.
     */
    public function __construct(private int $count = 5)
    {
    }

    /**
     * Get the resolved scalar constructor value.
     *
     * @return int
     */
    public function getCount(): int
    {
        return $this->count;
    }
}