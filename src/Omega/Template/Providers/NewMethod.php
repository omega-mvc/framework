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

use Omega\Template\Method;

/**
 * Class NewMethod
 *
 * Factory provider for constructing Method objects representing
 * class methods. It offers a streamlined way to initialize method
 * definitions when generating PHP class templates.
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
class NewMethod
{
    /**
     * Creates a new Method instance with the specified method name.
     *
     * @param string $name The name of the class method to create.
     * @return Method A Method object instantiated with the provided name.
     */
    public static function name(string $name): Method
    {
        return new Method($name);
    }
}
