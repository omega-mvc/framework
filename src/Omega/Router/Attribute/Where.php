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
 * Adds regex constraints to route parameters for a specific method.
 *
 * The array keys correspond to parameter names, and the values
 * are regex patterns used to validate them.
 *
 * Example usage:
 * ```php
 * #[Where(['id' => '\d+', 'slug' => '[a-z0-9-]+'])]
 * public function show($id, $slug) { ... }
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
#[Attribute(Attribute::TARGET_METHOD)]
final class Where
{
    /**
     * Parameter name → regex pattern mapping.
     *
     * @var array<string, string>
     */
    public array $pattern;

    /**
     * Initializes the Where attribute.
     *
     * @param array<string, string> $pattern Mapping of parameter names to regex patterns.
     */
    public function __construct(array $pattern)
    {
        $this->pattern = $pattern;
    }
}
