<?php

/**
 * Part of Omega - Tests\Support Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Support\Enum\Support;

use Omega\Support\Enum\AbstractEnum;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Concrete test implementation of AbstractEnum used for unit testing.
 *
 * Provides sample integer and string constants to verify enum behavior.
 *
 * @category   Tests
 * @package    Support
 * @subpackage Enum\Support
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[CoversNothing]
class TestEnum extends AbstractEnum
{
    /** @var int Integer enum constant representing the first test value. */
    public const int CONST_1 = 0;

    /** @var int Integer enum constant representing the second test value. */
    public const int CONST_2 = 1;

    /** @var string String enum constant representing a textual test value. */
    public const string CONST_3 = 'Const 3';

    /** @var array Custom string representations for specific enum values. */
    protected array $strings = [
        self::CONST_1 => 'Const 1',
    ];
}
