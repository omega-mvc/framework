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
use Omega\Router\Attribute\Route\Get;

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
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
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
