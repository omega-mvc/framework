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

namespace Omega\Router\Attribute\Route;

use Attribute;

/**
 * Defines a PUT route attribute for a method.
 *
 * Example usage:
 * ```php
 * #[Put('/users/{id}')]
 * public function update($id) { ... }
 * ```
 *
 * @category   Omega
 * @package    Router
 * @subpackage Attribute\Route
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class Put extends Route
{
    /**
     * Initializes the PUT route attribute.
     *
     * @param string $expression The URI pattern for this PUT route.
     */
    public function __construct(string $expression)
    {
        parent::__construct(['PUT'], $expression);
    }
}
