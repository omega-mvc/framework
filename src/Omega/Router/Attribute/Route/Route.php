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
 * Base class for defining HTTP method-specific route attributes.
 *
 * Stores the HTTP method(s) and the route expression.
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
class Route
{
    /**
     * Route definition containing HTTP method(s) and expression.
     *
     * @var array{method: string[], expression: string}
     */
    public array $route;

    /**
     * Initializes a Route with given HTTP methods and expression.
     *
     * @param string[] $method     HTTP methods this route responds to (e.g., ['GET', 'POST']).
     * @param string   $expression The URI pattern or route expression.
     */
    public function __construct(array $method, string $expression)
    {
        $this->route = [
            'method'     => $method,
            'expression' => $expression,
        ];
    }
}
