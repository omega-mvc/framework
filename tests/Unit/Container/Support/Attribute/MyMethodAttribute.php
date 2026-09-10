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

namespace Tests\Container\Support\Attribute;

use Attribute;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Marker attribute used to tag a method for reflection and attribute handling tests.
 *
 * @category   Tests
 * @package    Container
 * @subpackage Support\Attribute
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   GPL-3.0-or-later
 * @version    2.0.0
 */
#[CoversNothing]
#[Attribute(Attribute::TARGET_METHOD)]
final class MyMethodAttribute
{
}
