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

use Omega\Router\Attribute\Prefix;
use Omega\Router\Attribute\Route\Get;

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
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
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
