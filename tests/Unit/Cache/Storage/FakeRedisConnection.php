<?php

/**
 * Part of Omega - Tests Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Cache\Storage;

use Omega\Redis\RedisInterface;

use function array_values;

/**
 * Class FakeRedisConnection
 *
 * An in-memory {@see RedisInterface} fake used to exercise the handling paths
 * of the {@see RedisStorage} that require a non-functional redis backend: the
 * non-string guard of get(), the increment bootstrap, the non-integer
 * increment guard, the remember cache-hit shortcut, and the partial failure
 * results of setMultiple()/deleteMultiple().
 *
 * @category   Tests
 * @package    Cache
 * @subpackage Storage
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
final class FakeRedisConnection implements RedisInterface
{
    /**
     * FakeRedisConnection constructor.
     *
     * @param array<string, mixed> $storage The initial in-memory key-value store.
     */
    public function __construct(private array $storage = [])
    {
    }

    /** @var bool Whether set() reports success. */
    public bool $setResult = true;

    /** @var bool Whether exists() reports presence. */
    public bool $existsResult = false;

    /** @var int The value returned by del(). */
    public int $delResult = 1;

    /** @var list<array{method: string, arguments: list<mixed>}> Log of received calls. */
    public array $calls = [];

    /**
     * {@inheritdoc}
     */
    public function get(string $key): mixed
    {
        return $this->storage[$key] ?? false;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value, ?int $timeout = null): bool
    {
        $this->calls[] = ['method' => 'set', 'arguments' => [$key, $value, $timeout]];

        if ($this->setResult) {
            $this->storage[$key] = $value;
        }

        return $this->setResult;
    }

    /**
     * {@inheritdoc}
     */
    public function del(string|array $keys): int
    {
        $this->calls[] = ['method' => 'del', 'arguments' => [$keys]];

        return $this->delResult;
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $key): bool
    {
        return $this->existsResult;
    }

    /**
     * {@inheritdoc}
     */
    public function incr(string $key): int|false
    {
        $this->calls[] = ['method' => 'incr', 'arguments' => [$key]];

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function decr(string $key): int|false
    {
        $this->calls[] = ['method' => 'decr', 'arguments' => [$key]];

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function keys(string $pattern): array
    {
        return array_values($this->storage);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \LogicException Always, the fake never exposes a real client.
     */
    public function client(): \Redis
    {
        throw new \LogicException('FakeRedisConnection does not expose a Redis client.');
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): ?string
    {
        return 'fake';
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect(): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function command(string $command, array $arguments = []): mixed
    {
        $this->calls[] = ['method' => 'command', 'arguments' => [$command, $arguments]];

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function __call(string $method, array $arguments): mixed
    {
        $this->calls[] = ['method' => $method, 'arguments' => $arguments];

        return null;
    }
}