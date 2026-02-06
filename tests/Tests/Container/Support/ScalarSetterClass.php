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

/** @noinspection PhpGetterAndSetterCanBeReplacedWithPropertyHooksInspection */

declare(strict_types=1);

namespace Tests\Container\Support;

use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Class with a scalar setter method.
 *
 * Used to verify that setter injection is skipped or handled correctly
 * when parameters are scalar values.
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
class ScalarSetterClass
{
    /** @var string Sample scalar property. */
    public string $name = 'default';

    /**
     * Sets the name value.
     *
     * @param string $name
     * @return void
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
