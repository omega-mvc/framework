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
 * Base class used to test inheritance handling.
 *
 * Provides a public property and a method that can be discovered or inherited
 * by child classes during resolution or reflection-based operations.
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
class ParentClass
{
    /** @var string Sample inherited property. */
    public string $parentProperty = 'parent';

    /**
     * Sample inherited method with no side effects.
     *
     * @return void
     */
    public function parentMethod(): void
    {
    }
}
