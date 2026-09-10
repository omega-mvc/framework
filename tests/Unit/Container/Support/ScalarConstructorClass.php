<?php

/**
 * Part of Omega - Tests\Container Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   GPL-3.0-or-later
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Container\Support;

use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Class requiring a scalar constructor argument.
 *
 * Used to test container behavior when a dependency cannot be auto-resolved
 * because it is a scalar value.
 *
 * @category   Tests
 * @package    Container
 * @subpackage Support
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   GPL-3.0-or-later
 * @version    2.0.0
 */
#[CoversNothing]
final class ScalarConstructorClass
{
    /**
     * @param string $name Non-resolvable scalar dependency.
     */
    public function __construct(private string $name)
    {
    }

    /**
     * Get the scalar constructor value.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}
