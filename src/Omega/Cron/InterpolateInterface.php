<?php

/**
 * Part of Omega - Cron Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Cron;

/**
 * Interface InterpolateInterface
 *
 * Provides a contract for interpolating a message with contextual data.
 * Implementations of this interface are typically used for logging or
 * diagnostic output, where placeholders inside the message can be replaced
 * with values from the given context.
 *
 * @category  Omega
 * @package   Cron
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
interface InterpolateInterface
{
    /**
     * Interpolates the provided message with the given context values.
     *
     * The message may contain placeholder tokens (e.g., "{user}", "{id}")
     * that should be replaced with corresponding values from the context
     * array. Implementations may choose how to handle missing or additional
     * context keys.
     *
     * @param string $message The message string that may contain placeholders.
     * @param array<string, mixed> $context Key-value pairs used to replace placeholders in the message.
     * @return void
     */
    public function interpolate(string $message, array $context = []): void;
}
