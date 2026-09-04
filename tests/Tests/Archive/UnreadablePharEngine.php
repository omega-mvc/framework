<?php

/**
 * Part of Omega - Tests\Archive Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Archive;

use Omega\Archive\PharEngineInterface;

use function array_keys;
use function str_ends_with;

/**
 * Class UnreadablePharEngine
 *
 * A {@see PharEngineInterface} fake whose read operation always reports failure, allowing
 * the read-failure branch of the {@see PharAdapter} rename method to be exercised.
 *
 * @category   Tests
 * @package    Archive
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
final class UnreadablePharEngine implements PharEngineInterface
{
    /**
     * UnreadablePharEngine constructor.
     *
     * @param array<string,string> $members Holds the initial members keyed by name.
     * @return void
     */
    public function __construct(
        private array $members = []
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function contains(string $key): bool
    {
        return isset($this->members[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function read(string $key): string|false
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $key, string $content): void
    {
        $this->members[$key] = $content;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): void
    {
        unset($this->members[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function keys(): array
    {
        return array_keys($this->members);
    }

    /**
     * {@inheritdoc}
     */
    public function isDirectory(string $key): bool
    {
        return str_ends_with($key, '/');
    }

    /**
     * {@inheritdoc}
     */
    public function mtime(string $key): int
    {
        return 1234567890;
    }
}
