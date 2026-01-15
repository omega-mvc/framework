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
 * Class EmptyRouteClassController
 *
 * A minimal controller class used for testing resource route mapping.
 *
 * Contains methods with custom names to verify that the Router correctly maps
 * resource actions to different method names when using the `map` option.
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
#[CoversNothing]
class EmptyRouteClassController
{
    /**
     * Handle the "index" action mapped to "api".
     *
     * @return void
     */
    public function api(): void
    {
        echo 'works api';
    }

    /**
     * Handle the "create" action mapped to "apiCreate".
     *
     * @return void
     */
    public function apiCreate(): void
    {
        echo 'works apiCreate';
    }
}
