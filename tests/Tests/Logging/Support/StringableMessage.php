<?php

/**
 * Part of Omega - Tests\Logging Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Logging\Support;

use PHPUnit\Framework\Attributes\CoversNothing;
use Stringable;

/**
 * Class StringableMessage.
 *
 * A simple {@see Stringable} implementation used to verify that log drivers
 * accept stringable messages and cast them to strings before formatting.
 *
 * @category   Tests
 * @package    Logging
 * @subpackage Support
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[CoversNothing]
final class StringableMessage implements Stringable
{
    /** @var string The underlying string value. */
    private string $value;

    /**
     * Create a new StringableMessage instance.
     *
     * @param string $value The string value to expose.
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
