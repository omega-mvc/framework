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

use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Class NoRouteAttributeController
 *
 * A controller annotated with an irrelevant class-level attribute and whose
 * methods carry no HTTP route attribute. Used to verify that the Router
 * produces zero routes when a registered class exposes no Route attributes,
 * and that unrelated class attributes are ignored during attribute parsing.
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
#[MarkerAttribute]
#[CoversNothing]
final class NoRouteAttributeController
{
    /**
     * Public method without any HTTP route attribute.
     *
     * @return void
     */
    public function index(): void
    {
    }

    /**
     * Helper method without any HTTP route attribute.
     *
     * @return void
     */
    public function helper(): void
    {
    }
}