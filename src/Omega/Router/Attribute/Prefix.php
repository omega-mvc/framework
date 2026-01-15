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
 * Defines a URL prefix for all routes in a class.
 *
 * Useful for grouping related routes under a common path.
 *
 * Example usage:
 * ```php
 * #[Prefix('/admin')]
 * class AdminController { ... }
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
#[Attribute(Attribute::TARGET_CLASS)]
final class Prefix
{
    /**
     * The URI prefix applied to all routes in the class.
     *
     * @var string
     */
    public string $prefix;

    /**
     * Initializes the Prefix attribute.
     *
     * @param string $prefix URI prefix to apply to all routes.
     */
    public function __construct(string $prefix)
    {
        $this->prefix = $prefix;
    }
}
