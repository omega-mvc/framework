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
 * Class FakePharEngine
 *
 * An in-memory {@see PharEngineInterface} fake used to exercise the write, delete, and
 * rename paths of the {@see PharAdapter} without requiring a writable PHAR.
 *
 * @category   Tests
 * @package    Archive
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
final class FakePharEngine implements PharEngineInterface
{
    /**
     * FakePharEngine constructor.
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

    /**
     * Returns the current in-memory state.
     *
     * @return array<string,string> Holds the members keyed by name.
     */
    public function state(): array
    {
        return $this->members;
    }
}
