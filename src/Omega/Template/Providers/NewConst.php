<?php

/**
 * Part of Omega - Template Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Template\Providers;

use Omega\Template\Constant;

/**
 * Class NewConst
 *
 * Factory provider for creating Constant objects.
 * This class offers a concise, expressive API for instantiating
 * new Constant definitions used during template or class generation.
 * It centralizes object creation to keep calling code clean and fluent.
 *
 * @category   Omega
 * @package    Template
 * @subpackage Providers
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class NewConst
{
    /**
     * Creates a new Constant instance with the given name.
     *
     * @param string $name The identifier of the constant to be created.
     * @return Constant A newly instantiated Constant object.
     */
    public static function name(string $name): Constant
    {
        return new Constant($name);
    }
}
