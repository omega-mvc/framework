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
 * Fixture class used to verify reflection of public, protected, and private methods.
 *
 * This class exists only for testing reflection behavior and method visibility.
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
final class ClassWithMethods
{
    /**
     * Public method used to test reflection of public methods.
     *
     * @return void
     */
    public function publicMethod(): void
    {
    }

    /**
     * Protected method used to test reflection of non-public methods.
     *
     * @return void
     */
    protected function protectedMethod(): void
    {
    }

    /**
     * Private method used to test reflection of non-public methods.
     *
     * @return void
     * @noinspection PhpUnusedPrivateMethodInspection
     */
    private function privateMethod(): void
    {
    }
}
