<?php

/**
 * Part of Omega - Session Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Session;

use Closure;

use function array_key_exists;
use function array_keys;
use function array_merge;
use function array_unique;
use function array_values;
use function in_array;
use function is_string;
use function json_decode;
use function json_encode;

use const JSON_THROW_ON_ERROR;

/**
 * High-level session API.
 *
 * Wraps a {@see StorageInterface} driver and provides data management,
 * flash messaging, session bags, and lifecycle control. Session data is
 * encoded as JSON by the manager; drivers only handle raw strings.
 *
 * @category  Omega
 * @package   Session
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class SessionManager
{
    /** @var array<string, StorageInterface|Closure(): StorageInterface> */
    private array $drivers = [];

    private bool $started = false;

    private ?string $id = null;

    /** @var array<string, mixed> */
    private array $data = [];

    /** @var array<string, SessionBag> */
    private array $bags = [];

    /** @var list<string> New flash keys, available from the next request onwards. */
    private array $flashNew = [];

    /** @var list<string> Old flash keys, currently available but expired on the next request. */
    private array $flashOld = [];

    public function __construct(
        private string $defaultDriverName,
        StorageInterface $defaultDriver,
    ) {
        $this->drivers[$defaultDriverName] = $defaultDriver;
    }

    /** @return StorageInterface The currently active driver. */
    public function getDriver(): StorageInterface
    {
        return $this->resolve($this->defaultDriverName);
    }

    /** @param StorageInterface|Closure(): StorageInterface $driver */
    public function setDriver(string $name, Closure|StorageInterface $driver): self
    {
        $this->drivers[$name] = $driver;

        return $this;
    }

    public function start(): bool
    {
        if ($this->started) {
            return true;
        }

        $this->id ??= $this->getDriver()->createId();

        $this->loadSessionData();
        $this->expireFlashData();
        $this->started = true;

        return true;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function put(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return $this->data;
    }

    public function forget(string $key): void
    {
        unset($this->data[$key]);
    }

    public function flush(): void
    {
        $this->data    = [];
        $this->bags    = [];
        $this->flashNew = [];
        $this->flashOld = [];
    }

    /**
     * Flash a value for the next request.
     *
     * @param string $key   The flash key.
     * @param mixed  $value The value to store.
     */
    public function flash(string $key, mixed $value): void
    {
        $this->flashNew[] = $key;
        $this->data[$key] = $value;
    }

    /**
     * Retrieve a flashed value.
     *
     * @param string $key     The flash key.
     * @param mixed  $default Default value when the key is missing.
     * @return mixed The flashed value or default.
     */
    public function getFlash(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Determine whether a flash key is currently available.
     */
    public function hasFlash(string $key): bool
    {
        return in_array($key, $this->flashNew, true) || in_array($key, $this->flashOld, true);
    }

    /**
     * Clear all flash data.
     */
    public function clearFlash(): void
    {
        foreach ([...$this->flashNew, ...$this->flashOld] as $key) {
            unset($this->data[$key]);
        }

        $this->flashNew = [];
        $this->flashOld = [];
    }

    /**
     * Keep all flash data for another request.
     */
    public function reflash(): void
    {
        $this->flashOld = array_values(array_unique([...$this->flashOld, ...$this->flashNew]));
        $this->flashNew = [];
    }

    /**
     * Flash a value available only for the current request.
     */
    public function now(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
        $this->flashOld[] = $key;

        $filtered = [];

        foreach ($this->flashNew as $flashKey) {
            if ($flashKey !== $key) {
                $filtered[] = $flashKey;
            }
        }

        $this->flashNew = $filtered;
    }

    public function bag(string $name): SessionBag
    {
        if (!isset($this->bags[$name])) {
            $data = $this->data[$name] ?? null;

            if (!is_array($data)) {
                $data = [];
            }

            $bagData = [];
            foreach ($data as $key => $value) {
                if (is_string($key)) {
                    $bagData[$key] = $value;
                }
            }

            $this->data[$name] = $bagData;
            $this->bags[$name] = new SessionBag($name, $this->data[$name]);
        }

        return $this->bags[$name];
    }

    /**
     * Regenerate the session identifier.
     *
     * @param bool $destroy Whether to discard the stored data first.
     */
    public function regenerate(bool $destroy = false): bool
    {
        if ($this->id === null) {
            return false;
        }

        if ($destroy) {
            $this->getDriver()->destroy($this->id);
        }

        $this->id = $this->getDriver()->createId();

        return true;
    }

    public function destroy(): bool
    {
        if (!$this->started || $this->id === null) {
            return false;
        }

        $result = $this->getDriver()->destroy($this->id);
        $this->data      = [];
        $this->bags      = [];
        $this->id        = null;
        $this->started   = false;
        $this->flashNew  = [];
        $this->flashOld  = [];

        return $result;
    }

    public function save(): void
    {
        if (!$this->started || $this->id === null) {
            return;
        }

        $this->commitSessionData();
        $this->getDriver()->close();
        $this->started = false;
    }

    /** @return array{new: list<string>, old: list<string>} */
    private function flashSlice(): array
    {
        return [
            'new' => $this->flashNew,
            'old' => $this->flashOld,
        ];
    }

    /** @param array{new?: mixed, old?: mixed} $flash The raw flash slice. */
    private function restoreFlashSlice(array $flash): void
    {
        $this->flashNew = is_array($flash['new'] ?? null) ? $this->stringList($flash['new']) : [];
        $this->flashOld = is_array($flash['old'] ?? null) ? $this->stringList($flash['old']) : [];
    }

    /**
     * @param array<mixed> $values
     * @return list<string>
     */
    private function stringList(array $values): array
    {
        $list = [];

        foreach ($values as $value) {
            if (is_string($value)) {
                $list[] = $value;
            }
        }

        return $list;
    }

    private function loadSessionData(): void
    {
        $raw = $this->getDriver()->read($this->id ?? '');

        if ($raw === false || $raw === '') {
            $this->data = [];
            return;
        }

        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($decoded)) {
            $this->data = [];
            return;
        }

        $data = [];

        foreach ($decoded as $key => $value) {
            if (is_string($key)) {
                $data[$key] = $value;
            }
        }

        $this->data = $data;

        if (is_array($decoded['_flash'] ?? null)) {
            $this->restoreFlashSlice($decoded['_flash']);
        }

        unset($this->data['_flash']);
    }

    private function commitSessionData(): void
    {
        $this->data['_flash'] = $this->flashSlice();

        $json = json_encode($this->data, JSON_THROW_ON_ERROR);
        $this->getDriver()->write($this->id ?? '', $json);
    }

    private function expireFlashData(): void
    {
        foreach ($this->flashOld as $key) {
            unset($this->data[$key]);
        }

        $this->flashOld = $this->flashNew;
        $this->flashNew = [];
    }

    private function resolve(string $name): StorageInterface
    {
        $driver = $this->drivers[$name];

        if ($driver instanceof Closure) {
            $driver = $driver();
            $this->drivers[$name] = $driver;
        }

        return $driver;
    }
}