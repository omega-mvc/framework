<?php

/**
 * Part of Omega - Console Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Console\Style;

use Omega\Console\Traits\AlertTrait;

/**
 * Class Alert
 *
 * Provides a convenient interface to render alert messages in the console.
 * This class uses the AlertTrait to handle different alert types such as info, warn, and error.
 *
 * Example usage:
 * ```php
 * Alert::render()->info('Information message');
 * Alert::render()->warn('Warning message');
 * Alert::render()->error('Error message');
 * ```
 * @category   Omega
 * @package    Console
 * @subpackage Style
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class Alert
{
    use AlertTrait;

    /**
     * Create a new Alert instance.
     *
     * This static method provides a convenient entry point to render alerts.
     * Returns a new instance of Alert for method chaining.
     *
     * @return static Returns a static instance of Alert
     */
    public static function render(): static
    {
        return new self();
    }
}
