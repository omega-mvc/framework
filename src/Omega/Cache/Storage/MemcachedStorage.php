<?php

/** @noinspection PhpComposerExtensionStubsInspection */

declare(strict_types=1);

namespace Omega\Cache\Storage;

use DateInterval;
use Memcached as PhpMemcached;
use Omega\Cache\AbstractCache;

use function extension_loaded;
use function is_int;
use function is_string;
use function serialize;
use function unserialize;

class MemcachedStorage extends AbstractCache
{
    private PhpMemcached $memcached;

    private string $prefix = '';

    /**
     * @param array{
     *     ttl?: int|DateInterval,
     *     servers?: list<array{host: string, port: int}>,
     *     host?: string,
     *     port?: int,
     *     username?: string,
     *     password?: string,
     *     prefix?: string,
     *     timeout?: int,
     * } $options
     */
    public function __construct(array $options)
    {
        parent::__construct($options['ttl'] ?? 3600);

        $this->memcached = new PhpMemcached();

        if (isset($options['timeout'])) {
            $this->memcached->setOption(PhpMemcached::OPT_CONNECT_TIMEOUT, (int) $options['timeout']);
        }

        $servers = $options['servers'] ?? [[
            'host' => $options['host'] ?? '127.0.0.1',
            'port' => $options['port'] ?? 11211,
        ]];

        foreach ($servers as $server) {
            $this->memcached->addServer((string) $server['host'], (int) $server['port']);
        }

        if (isset($options['username'], $options['password'])) {
            $this->memcached->setSaslAuthData((string) $options['username'], (string) $options['password']);
        }

        $this->prefix = (string) ($options['prefix'] ?? '');
    }

    public static function isSupported(): bool
    {
        if (!extension_loaded('memcached')) {
            return false;
        }

        try {
            $memcached = new PhpMemcached();
            $memcached->addServer('127.0.0.1', 11211);

            $memcached->getVersion();

            return $memcached->getResultCode() === PhpMemcached::RES_SUCCESS;
        } catch (\MemcachedException) {
            return false;
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->memcached->get($this->prefix . $key);

        if (PhpMemcached::RES_NOTFOUND === $this->memcached->getResultCode()) {
            return $default;
        }

        if (false === is_string($value)) {
            return $default;
        }

        return unserialize($value, ['allowed_classes' => false]);
    }

    public function set(string $key, mixed $value, int|DateInterval|null $ttl = null): bool
    {
        $serializedValue = serialize($value);
        $seconds         = $this->calculateTTLInSeconds($ttl);

        return $this->memcached->set($this->prefix . $key, $serializedValue, $seconds);
    }

    public function delete(string $key): bool
    {
        return $this->memcached->delete($this->prefix . $key);
    }

    public function clear(): bool
    {
        return $this->memcached->flush();
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }

    /**
     * @param iterable<string, mixed> $values The set of key-value pairs to cache.
     */
    public function setMultiple(iterable $values, int|DateInterval|null $ttl = null): bool
    {
        $success = true;

        foreach ($values as $key => $value) {
            $success = $this->set($key, $value, $ttl) && $success;
        }

        return $success;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        $success = true;

        foreach ($keys as $key) {
            $success = $this->delete($key) && $success;
        }

        return $success;
    }

    public function has(string $key): bool
    {
        $this->memcached->get($this->prefix . $key);

        return PhpMemcached::RES_NOTFOUND !== $this->memcached->getResultCode();
    }

    public function increment(string $key, int $value): int
    {
        if (false === $this->has($key)) {
            $this->set($key, $value, $this->defaultTTL);

            return $value;
        }

        $currentValue = $this->get($key);

        if (false === is_int($currentValue)) {
            throw new \InvalidArgumentException('Value to increment must be an integer.');
        }

        $newValue = $currentValue + $value;

        $this->set($key, $newValue, $this->defaultTTL);

        return $newValue;
    }

    public function decrement(string $key, int $value): int
    {
        return $this->increment($key, -1 * $value);
    }

    public function remember(string $key, \Closure $callback, int|DateInterval|null $ttl): mixed
    {
        $value = $this->get($key);

        if (null !== $value) {
            return $value;
        }

        $value = $callback();
        $this->set($key, $value, $ttl);

        return $value;
    }

    private function calculateTTLInSeconds(int|DateInterval|null $ttl): int
    {
        if (null === $ttl) {
            return $this->resolveDefaultTTL();
        }

        if ($ttl instanceof DateInterval) {
            return $this->intervalToSeconds($ttl);
        }

        return $ttl;
    }

    private function resolveDefaultTTL(): int
    {
        if ($this->defaultTTL instanceof DateInterval) {
            return $this->intervalToSeconds($this->defaultTTL);
        }

        return $this->defaultTTL;
    }

    private function intervalToSeconds(DateInterval $interval): int
    {
        $now = new \DateTimeImmutable();

        return $now->add($interval)->getTimestamp() - $now->getTimestamp();
    }
}
