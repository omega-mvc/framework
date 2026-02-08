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

namespace Tests\Container\Support;

use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Test fixture providing callable instance and static methods
 * for validating method invocation and dependency injection.
 *
 * @category   Tests
 * @package    Container
 * @subpackage Support
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[CoversNothing]
final class CallableClass
{
    /**
     * Instance method used to test callable resolution with injected dependencies.
     *
     * @param DependencyClass $dep Injected dependency.
     * @return DependencyClass The resolved dependency.
     */
    public function someMethod(DependencyClass $dep): DependencyClass
    {
        return $dep;
    }

    /**
     * Static method used to test static callable resolution with injected dependencies.
     *
     * @param DependencyClass $dep Injected dependency.
     * @return DependencyClass The resolved dependency.
     */
    public static function staticMethod(DependencyClass $dep): DependencyClass
    {
        return $dep;
    }
}
