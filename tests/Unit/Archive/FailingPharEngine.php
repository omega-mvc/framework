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
use RuntimeException;

use function array_keys;
use function str_ends_with;

/**
 * Class FailingPharEngine
 *
 * A {@see PharEngineInterface} fake whose write operation always throws, allowing the
 * rename failure branch of the {@see PharAdapter} to be exercised.
 *
 * @category   Tests
 * @package    Archive
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
final class FailingPharEngine implements PharEngineInterface
{
    /**
     * FailingPharEngine constructor.
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
        return $this->members[$key] ?? false;
    }

    /**
     * {@inheritdoc}
     * @throws RuntimeException Always throws to simulate a failed rename.
     */
    public function write(string $key, string $content): void
    {
        throw new RuntimeException('cannot write to a directory member');
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
