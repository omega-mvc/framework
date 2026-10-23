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

namespace Tests\Router\Support;

use Omega\Router\Attribute\Middleware;
use Omega\Router\Attribute\Name;
use Omega\Router\Attribute\Prefix;
use Omega\Router\Attribute\Route\Get;

/**
 * Class AttributeMiddlewareNamePrefixOrderedController
 *
 * Carries the full Middleware + Name + Prefix attribute set in the canonical
 * declaration order, mirroring TestRouteAttribute without an unrelated
 * attribute in between.
 *
 * @category   Tests
 * @package    Router
 * @subpackage Support
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[Middleware([TestMiddleware::class])]
#[Name('full.')]
#[Prefix('/full')]
final class AttributeMiddlewareNamePrefixOrderedController
{
    /**
     * Plain route.
     *
     * @return void
     */
    #[Get('/index')]
    public function index(): void
    {
    }
}
