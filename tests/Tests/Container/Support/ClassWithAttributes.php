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

use Tests\Container\Support\Attribute\MyClassAttribute;
use Tests\Container\Support\Attribute\MyMethodAttribute;
use Tests\Container\Support\Attribute\MyPropertyAttribute;

/**
 * Fixture class used to verify that class, property and method attributes are correctly reflected.
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
#[MyClassAttribute]
class ClassWithAttributes
{
    /** @var mixed Property annotated with a custom attribute for reflection testing purposes. */
    #[MyPropertyAttribute]
    public mixed $propertyWithAttribute;

    /**
     * Method annotated with a custom attribute for reflection testing purposes.
     *
     * @return void
     */
    #[MyMethodAttribute]
    public function methodWithAttribute(): void
    {
    }
}
