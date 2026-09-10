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
 * Class with a private constructor.
 *
 * Used to verify that the container correctly handles classes that cannot
 * be instantiated due to constructor visibility.
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
final class PrivateConstructorClass
{
    /**
     * Prevents direct instantiation.
     */
    private function __construct()
    {
    }
}
