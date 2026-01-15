<?php

/**
 * Part of Omega - Tests\Router Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Router\Attribute;

use Omega\Router\Attribute\Middleware;
use Omega\Router\Attribute\Name;
use Omega\Router\Attribute\Prefix;
use Omega\Router\Attribute\Route\Get;
use Omega\Router\Attribute\Where;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class TestRouteAttribute
 *
 * A support class for testing attribute-based route registration in the Omega Router system.
 *
 * This class demonstrates the use of multiple route-related attributes at both the class
 * and method level, including:
 *   - Middleware: specifying middleware to be applied to the route or route group.
 *   - Name: defining a name prefix for the route or a specific route name.
 *   - Prefix: setting a URL prefix for the route group.
 *   - Get: defining a GET HTTP route.
 *   - Where: defining parameter validation patterns for route placeholders.
 *
 * The class-level attributes apply globally to all routes defined within this class:
 *   - Name('test.') → prepends 'test.' to all route names
 *   - Middleware(['testmiddeleware_class']) → applied to all routes in the class
 *   - Prefix('/test') → prepends '/test' to all route URIs
 *
 * The method-level attributes override or extend class-level configuration:
 *   - Name('test') → sets the specific route name
 *   - Middleware(['testmiddeleware_method']) → adds method-specific middleware
 *   - Where(['{id}' => '(\d+)']) → enforces that the {id} parameter must be numeric
 *   - Get('/{id}/test') → defines the route URI pattern for GET requests
 *
 * This class is intended solely for testing the behavior of route attribute parsing
 * and middleware/parameter integration, and does not contain any application logic.
 *
 * @category   Tests
 * @package    Router
 * @subpackage Attribute
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[CoversClass(Middleware::class)]
#[CoversClass(Name::class)]
#[CoversClass(Prefix::class)]
#[CoversClass(Get::class)]
#[CoversClass(Where::class)]
#[Name('test.')]
#[Middleware(['testmiddeleware_class'])]
#[Prefix('/test')]
final class TestRouteAttribute
{
    /**
     * Test method index.
     *
     * @return void
     */
    #[Get('/{id}/test')]
    #[Name('test')]
    #[Middleware(['testmiddeleware_method'])]
    #[Where(['{id}' => '(\d+)'])]
    public function index(): void
    {
    }
}
