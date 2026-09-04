<?php

/** @noinspection PhpComposerExtensionStubsInspection */

declare(strict_types=1);

namespace Omega\Cache\Storage;

use Closure;
use DateInterval;
use DateTimeImmutable;
use InvalidArgumentException;
use Omega\Cache\AbstractCache;
use Omega\Cache\Exceptions\CacheConfigurationException;

use function apcu_clear_cache;
use function apcu_delete;
use function apcu_enabled;
use function apcu_exists;
use function apcu_fetch;
use function apcu_inc;
use function apcu_key_info;
use function apcu_store;
use function array_key_exists;
use function extension_loaded;
use function is_array;
use function is_float;
use function is_int;

class ApcuStorage extends AbstractCache
{
    private string $prefix = '';

    /**
     * @param array{ttl?: int|DateInterval, prefix?: string} $options
     */
    public function __construct(array $options)
    {
        parent::__construct($options['ttl'] ?? 3600);

        if (empty($options['prefix'])) {
            throw new CacheConfigurationException();
        }

        $this->prefix = (string) $options['prefix'];
    }

    public static function isSupported(): bool
    {
        return extension_loaded('apcu') && apcu_enabled();
    }

    /**
     * Get info of storage.
     *
     * @return array{value: mixed, timestamp?: int, mtime?: float}|array{}
     */
    public function getInfo(string $key): array
    {
        $info = apcu_key_info($this->prefix . $key);

        if (null === $info) {
            return [];
        }

        $ttl          = $info['ttl'] ?? 0;
        $creationTime = $info['creation_time'] ?? 0;
        $mtime        = $info['mtime'] ?? 0;

        if (!is_int($ttl) || !is_int($creationTime) || (!is_int($mtime) && !is_float($mtime))) {
            return [];
        }

        return [
            'value'     => $this->get($key),
            'timestamp' => $ttl > 0 ? $creationTime + $ttl : 0,
            'mtime'     => (float) $mtime,
        ];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $success = false;
        $value   = apcu_fetch($this->prefix . $key, $success);

        return $success ? $value : $default;
    }

    public function set(string $key, mixed $value, int|DateInterval|null $ttl = null): bool
    {
        return apcu_store($this->prefix . $key, $value, $this->calculateTTL($ttl));
    }

    public function delete(string $key): bool
    {
        return apcu_delete($this->prefix . $key);
    }

    public function clear(): bool
    {
        return apcu_clear_cache();
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $prefixedKeys = [];
        foreach ($keys as $key) {
            $prefixedKeys[] = $this->prefix . $key;
        }

        $values = apcu_fetch($prefixedKeys);

        if (false === is_array($values)) {
            $values = [];
        }

        $result = [];

        foreach ($keys as $key) {
            $prefixedKey  = $this->prefix . $key;
            $result[$key] = array_key_exists($prefixedKey, $values) ? $values[$prefixedKey] : $default;
        }

        return $result;
    }

    /**
     * @param iterable<string, mixed> $values The set of key-value pairs to cache.
     */
    public function setMultiple(iterable $values, int|DateInterval|null $ttl = null): bool
    {
        $prefixedValues = [];
        foreach ($values as $key => $value) {
            $prefixedValues[$this->prefix . $key] = $value;
        }

        $result = apcu_store($prefixedValues, null, $this->calculateTTL($ttl));

        return empty($result);
    }

    public function deleteMultiple(iterable $keys): bool
    {
        $prefixedKeys = [];
        foreach ($keys as $key) {
            $prefixedKeys[] = $this->prefix . $key;
        }

        $result = apcu_delete($prefixedKeys);

        return empty($result);
    }

    public function has(string $key): bool
    {
        return apcu_exists($this->prefix . $key);
    }

    public function increment(string $key, int $value): int
    {
        if ($this->has($key) && false === is_int($this->get($key))) {
            throw new InvalidArgumentException('Value increment must be integer.');
        }

        $result = apcu_inc($this->prefix . $key, $value, $success);

        if (false === $result) {
            $this->set($key, $value, 0);

            return $value;
        }

        return $result;
    }

    public function decrement(string $key, int $value): int
    {
        return $this->increment($key, $value * -1);
    }

    public function remember(string $key, Closure $callback, int|DateInterval|null $ttl): mixed
    {
        $value = $this->get($key);

        if (null !== $value) {
            return $value;
        }

        $this->set($key, $value = $callback(), $ttl);

        return $value;
    }

    private function calculateTTL(int|DateInterval|null $ttl): int
    {
        if (null === $ttl) {
            $ttl = $this->defaultTTL;
        }

        if ($ttl instanceof DateInterval) {
            $now = new DateTimeImmutable();

            return $now->add($ttl)->getTimestamp() - $now->getTimestamp();
        }

        return $ttl;
    }
}
