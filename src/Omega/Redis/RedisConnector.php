<?php

/** @noinspection PhpComposerExtensionStubsInspection */

namespace Omega\Redis;

use Redis as PhpRedis;

class RedisConnector
{
    /**
     * @param array{
     *     host?: string,
     *     port?: int,
     *     timeout?: float,
     *     retry_interval?: int,
     *     read_timeout?: float,
     *     persistent?: bool,
     *     persistent_id?: string,
     *     password?: string,
     *     database?: int,
     *     unix_socket?: string,
     * } $config
     *
     * @return PhpRedis
     */
    public function connect(array $config): object
    {
        if (false === extension_loaded('redis')) {
            throw new \RuntimeException('The Redis extension is not loaded.');
        }

        $redis = new PhpRedis();

        $timeout       = (float) ($config['timeout'] ?? 0.0);
        $retryInterval = (int) ($config['retry_interval'] ?? 0);
        $readTimeout   = (float) ($config['read_timeout'] ?? 0.0);
        $persistent    = (bool) ($config['persistent'] ?? false);
        $persistentId  = (string) ($config['persistent_id'] ?? '');

        try {
            if (isset($config['unix_socket'])) {
                $this->establishConnection(
                    $redis,
                    (string) $config['unix_socket'],
                    0,
                    $timeout,
                    $persistentId,
                    $retryInterval,
                    $persistent
                );
            } else {
                $this->establishConnection(
                    $redis,
                    (string) ($config['host'] ?? '127.0.0.1'),
                    (int) ($config['port'] ?? 6379),
                    $timeout,
                    $persistentId,
                    $retryInterval,
                    $persistent
                );
            }

            if ($readTimeout > 0) {
                $redis->setOption(PhpRedis::OPT_READ_TIMEOUT, $readTimeout);
            }

            if (isset($config['password']) && $config['password'] !== '') {
                $redis->auth((string) $config['password']);
            }

            if (isset($config['database'])) {
                $redis->select((int) $config['database']);
            }
        } catch (\RedisException $e) {
            throw new \RedisException("Could not connect to Redis: {$e->getMessage()}", (int) $e->getCode(), $e);
        }

        return $redis;
    }

    /**
     * Establish the connection to Redis.
     *
     * @param PhpRedis $redis         The Redis client instance.
     * @param string   $host          The Redis host or unix socket path.
     * @param int      $port          The Redis port.
     * @param float    $timeout       The connection timeout in seconds.
     * @param string   $persistentId  Identifier for persistent connections.
     * @param int      $retryInterval The retry interval for connecting.
     * @param bool     $persistent    Whether to use a persistent connection.
     */
    protected function establishConnection(
        PhpRedis $redis,
        string $host,
        int $port,
        float $timeout,
        string $persistentId,
        int $retryInterval,
        bool $persistent
    ): void {
        if ($persistent) {
            $redis->pconnect($host, $port, $timeout, $persistentId, $retryInterval);

            return;
        }

        $redis->connect($host, $port, $timeout, $persistentId, $retryInterval);
    }
}
