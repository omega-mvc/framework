<?php

/**
 * Part of Omega - Session Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Session\Facade;

use Omega\Facade\AbstractFacade;

/**
 * Facade for the Session service.
 *
 * Provides static access to the underlying {@see \Omega\Session\SessionManager}
 * resolved from the application container.
 *
 * @category   Omega
 * @package    Session
 * @subpackage Facade
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 *
 * @method static \Omega\Session\SessionManager getDriver(?string $name = null)
 * @method static bool   start()
 * @method static ?string getId()
 * @method static void   setId(string $id)
 * @method static bool   isValid()
 * @method static bool   isInvalidate(int $maxLifetime = 7200)
 * @method static bool   has(string $key)
 * @method static mixed  get(string $key, mixed $default = null)
 * @method static void   put(string $key, mixed $value)
 * @method static array<string, mixed> all()
 * @method static void   forget(string $key)
 * @method static void   flush()
 * @method static void   flash(string $key, mixed $value)
 * @method static mixed  getFlash(string $key, mixed $default = null)
 * @method static bool   hasFlash(string $key)
 * @method static void   clearFlash()
 * @method static void   reflash()
 * @method static void   now(string $key, mixed $value)
 * @method static \Omega\Session\SessionBag bag(string $name)
 * @method static bool   regenerate(bool $destroy = false)
 * @method static bool   destroy()
 * @method static void   save()
 *
 * @see \Omega\Session\SessionManager
 */
final class Session extends AbstractFacade
{
    /**
     * {@inheritdoc}
     */
    public static function getFacadeAccessor(): string
    {
        return 'session';
    }
}
