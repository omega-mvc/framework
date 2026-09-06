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
 * Class without setter-style injection methods.
 *
 * Used to ensure non-annotated methods are not treated as injectable setters.
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
final class NonSetterClass
{
    /** @var bool Indicates whether the method was called */
    public bool $called = false;

    /**
     * Regular method that should not be treated as a setter.
     *
     * @param DependencyClass $dependency Resolved dependency.
     * @return void
     * @noinspection PhpUnusedParameterInspection
     */
    public function doSomething(DependencyClass $dependency): void
    {
        $this->called = true;
    }
}
