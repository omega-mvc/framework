<?php

/**
 * Part of Omega - Tests\Container Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Container\Fixtures;

use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Class with a static setter method.
 *
 * Used to verify whether static injection methods are ignored or handled
 * explicitly by the container.
 *
 * @category   Tests
 * @package    Container
 * @subpackage Fixtures
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[CoversNothing]
class StaticSetterClass
{
    /** @var bool Flag indicating whether the static method was called. */
    public static bool $called = false;

    /**
     * Static setter method.
     *
     * @param DependencyClass $dependency
     * @return void
     * @noinspection PhpUnusedParameterInspection
     */
    public static function setStaticDependency(DependencyClass $dependency): void
    {
        self::$called = true;
    }
}
