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

use Omega\Template\Property;

/**
 * Class NewProperty
 *
 * Factory provider responsible for creating Property objects.
 * It provides a minimal and expressive API for initializing
 * class property definitions in the template generation system.
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
class NewProperty
{
    /**
     * Creates a new Property instance with the given property name.
     *
     * @param string $name The identifier of the property to generate.
     * @return Property A newly created Property object initialized with the provided name.
     */
    public static function name(string $name): Property
    {
        return new Property($name);
    }
}
