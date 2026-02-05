<?php

/**
 * Part of Omega - Tests\Config Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Config\Source;

use Omega\Config\Source\AbstractSource;
use PHPUnit\Framework\Attributes\CoversNothing;

use function compact;
use function rtrim;

/**
 * Simple test-only implementation of AbstractSource.
 *
 * This class provides a concrete fetch() method that wraps the raw file
 * content returned by fetchContent() into a structured array. It exists
 * solely as a lightweight helper for testing behavior shared across
 * AbstractSource subclasses.
 *
 * @category   Tests
 * @package    Config
 * @subpackage Source
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[CoversNothing]
final class TestConfigurationSource extends AbstractSource
{
    /**
     * {@inheritdoc}
     */
    public function fetch(): array
    {
        $content = rtrim($this->fetchContent());

        return compact('content');
    }
}
