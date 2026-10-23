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

use Omega\Router\Attribute\Name;
use Omega\Router\Attribute\Prefix;
use Omega\Router\Attribute\Route\Route;

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
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
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
