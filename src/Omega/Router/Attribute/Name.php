<?php

/**
 * Part of Omega - Router Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Router\Attribute;

use Attribute;

/**
 * Assigns a name to a class or method for routing purposes.
 *
 * This name can be used to generate URLs or reference routes
 * programmatically.
 *
 * Example usage:
 * ```php
 * #[Name('user.index')]
 * class UserController { ... }
 *
 * #[Name('user.create')]
 * public function create() { ... }
 * ```
 *
 * @category   Omega
 * @package    Router
 * @subpackage Attribute
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class Name
{
    /**
     * The route name.
     *
     * @var string
     */
    public string $name;

    /**
     * Initializes the Name attribute.
     *
     * @param string $name Name of the route.
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
