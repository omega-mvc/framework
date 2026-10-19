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
use Omega\Router\Attribute\Where;
use Omega\Router\Attribute\Route\Get;
use Omega\Router\Attribute\Route\Route;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Class AttributeCombinationController
 *
 * A controller whose class and methods combine routing attributes in the
 * remaining permutations not covered by TestRouteAttribute, so that every
 * branch of the attribute walker is exercised:
 *
 *   - class level: Middleware + Name + Prefix in a different order
 *   - method level: an unrelated attribute that matches no routing attribute,
 *     a bare Name attribute, a Where attribute without a route, and a method
 *     with all attribute kinds.
 *
 * @category   Tests
 * @package    Router
 * @subpackage Support
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[Prefix('/combo')]
#[Name('combo.')]
#[Middleware([TestMiddleware::class])]
final class AttributeCombinationController
{
    /**
     * Route that carries every attribute kind at method level.
     *
     * @return void
     */
    #[Get('/all')]
    #[Where(['{id}' => '(\d+)'])]
    #[Name('all')]
    #[Middleware([TestMiddleware::class])]
    public function all(): void
    {
    }

    /**
     * Method decorated with an unrelated attribute only.
     *
     * @return void
     */
    #[CoversNothing]
    public function marked(): void
    {
    }

    /**
     * Method carrying a bare Name attribute and no route.
     *
     * @return void
     */
    #[Name('bare')]
    public function bare(): void
    {
    }
}

/**
 * Class AttributeMiddlewarePrefixController
 *
 * Carries class-level Middleware + Prefix attributes (no Name) so the
 * walker exits through the Prefix arm without touching the Name arm.
 *
 * @category   Tests
 * @package    Router
 * @subpackage Support
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[Middleware([TestMiddleware::class])]
#[Prefix('/mp')]
final class AttributeMiddlewarePrefixController
{
    /**
     * Plain route.
     *
     * @return void
     */
    #[Get('/ping')]
    public function ping(): void
    {
    }
}

/**
 * Class AttributeNamePrefixController
 *
 * Carries class-level Name + Prefix attributes (no Middleware) so the
 * walker resolves the prefix without inheriting root middleware.
 *
 * @category   Tests
 * @package    Router
 * @subpackage Support
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[Name('np.')]
#[Prefix('/np')]
final class AttributeNamePrefixController
{
    /**
     * Plain route.
     *
     * @return void
     */
    #[Route(['get'], '/item')]
    public function item(): void
    {
    }
}

/**
 * Class AttributeSingleMiddlewareController
 *
 * Carries a single class-level Middleware attribute so the class walker
 * exits through the Middleware arm only.
 *
 * @category   Tests
 * @package    Router
 * @subpackage Support
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[Middleware([TestMiddleware::class])]
final class AttributeSingleMiddlewareController
{
    /**
     * Plain route.
     *
     * @return void
     */
    #[Get('/mw')]
    public function index(): void
    {
    }
}

/**
 * Class AttributeSingleNameController
 *
 * Carries a single class-level Name attribute so the class walker exits
 * through the Name arm only.
 *
 * @category   Tests
 * @package    Router
 * @subpackage Support
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[Name('n.')]
final class AttributeSingleNameController
{
    /**
     * Plain route.
     *
     * @return void
     */
    #[Get('/named')]
    public function index(): void
    {
    }
}

/**
 * Class AttributeSinglePrefixController
 *
 * Carries a single class-level Prefix attribute so the class walker exits
 * through the Prefix arm only.
 *
 * @category   Tests
 * @package    Router
 * @subpackage Support
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[Prefix('/single')]
final class AttributeSinglePrefixController
{
    /**
     * Plain route.
     *
     * @return void
     */
    #[Get('/prefixed')]
    public function index(): void
    {
    }
}

/**
 * Class AttributeMiddlewareNameController
 *
 * Carries class-level Middleware + Name attributes (no Prefix) so the class
 * walker resolves the name without a prefix.
 *
 * @category   Tests
 * @package    Router
 * @subpackage Support
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[Middleware([TestMiddleware::class])]
#[Name('mn.')]
final class AttributeMiddlewareNameController
{
    /**
     * Plain route.
     *
     * @return void
     */
    #[Get('/pair')]
    public function index(): void
    {
    }
}

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
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
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