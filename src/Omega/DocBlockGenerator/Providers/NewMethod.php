<?php

/**
 * Part of Omega - DocBlockGenerator Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   GPL-3.0-or-later
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\DocBlockGenerator\Providers;

use Omega\DocBlockGenerator\Method;

/**
 * Class NewMethod
 *
 * Factory provider for generating Method objects representing
 * PHP functions (non-class methods). It enables a fluent, uniform
 * construction mechanism for template-based function definitions.
 *
 * @category   Omega
 * @package   DocBlockGenerator
 * @subpackage Providers
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   GPL-3.0-or-later
 * @version    2.0.0
 */
class NewMethod
{
    /**
     * Creates a new Method instance representing a function.
     *
     * @param string $name The function name to assign to the Method object.
     * @return Method A newly created Method instance configured with the given name.
     */
    public static function name(string $name): Method
    {
        return new Method($name);
    }
}
