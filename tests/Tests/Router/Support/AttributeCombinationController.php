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
