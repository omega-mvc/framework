<?php

/**
 * Part of Omega - Testing Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Testing;

use ArrayAccess;
use Exception;
use PHPUnit\Framework\Assert;
use Omega\Http\Response;
use ReturnTypeWillChange;

use function array_key_exists;
use function is_array;
use function Omega\Collection\data_get;

/**
 * TestJsonResponse is a wrapper around the Response class designed for testing JSON responses.
 * It allows assertions on response data as arrays and provides convenient array access to the response content.
 *
 * Implements ArrayAccess to allow array-like access to the response data.
 *
 * Example usage:
 * ```php
 * $response = new TestJsonResponse($response);
 * $response->assertEqual('data.key', 'value');
 * ```
 *
 * @category   Omega
 * @package    Testing
 * @subpackage Traits
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 *
 * @implements ArrayAccess<string, mixed>
 */
class TestJsonResponse extends TestResponse implements ArrayAccess
{
    /** @var array<string, mixed> The response data cast to an array. */
    private array $responseData;

    /**
     * Constructor.
     *
     * Initializes the TestJsonResponse instance from a Response object.
     *
     * @param Response $response The response object to wrap
     * @return void
     * @throws Exception If the response content is not an array
     */
    public function __construct(Response $response)
    {
        $this->response     = $response;
        $this->responseData = (array) $response->getContent();
        if (!is_array($response->getContent())) {
            throw new Exception('Response body is not Array.');
        }
    }

    /**
     * Set the response data.
     *
     * @param array<string, mixed> $responseData The array to replace the current response data
     * @return $this
     */
    public function setResponseData(array $responseData): self
    {
        $this->responseData = $responseData;
        return $this;
    }

    /**
     * Get the "data" part of the response.
     *
     * @return mixed The value stored under the 'data' key
     * @return void
     */
    public function getData(): mixed
    {
        return $this->responseData['data'];
    }

    /**
     * Check if an offset exists.
     *
     * @param mixed $offset The array key
     * @return bool True if the key exists, false otherwise
     * @return void
     */
    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->responseData);
    }

    /**
     * Get the value at the given offset.
     *
     * @param mixed $offset The array key
     * @return mixed The value stored at the given key
     * @return void
     */
    #[ReturnTypeWillChange]
    public function offsetGet(mixed $offset): mixed
    {
        return $this->responseData[$offset];
    }

    /**
     * Set a value at the given offset.
     *
     * @param mixed $offset The array key
     * @param mixed $value  The value to set
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->responseData[$offset] = $value;
    }

    /**
     * Unset the value at the given offset.
     *
     * @param mixed $offset The array key
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->responseData[$offset]);
    }

    /**
     * Assert that a given data key equals the expected value.
     *
     * @param string $dataKey The key path in the response data
     * @param mixed  $value   The expected value
     * @return void
     */
    public function assertEqual(string $dataKey, mixed $value): void
    {
        $dataGet = data_get($this->responseData, $dataKey);
        Assert::assertEquals($dataGet, $value);
    }

    /**
     * Assert that a given data key is true.
     *
     * @param string $dataKey The key path in the response data
     * @param string $message Optional assertion message
     * @return void
     */
    public function assertTrue(string $dataKey, string $message = ''): void
    {
        $dataGet = data_get($this->responseData, $dataKey);
        Assert::assertTrue($dataGet, $message);
    }

    /**
     * Assert that a given data key is false.
     *
     * @param string $dataKey The key path in the response data
     * @param string $message Optional assertion message
     * @return void
     */
    public function assertFalse(string $dataKey, string $message = ''): void
    {
        $dataGet = data_get($this->responseData, $dataKey);
        Assert::assertFalse($dataGet, $message);
    }

    /**
     * Assert that a given data key is null.
     *
     * @param string $dataKey The key path in the response data
     * @param string $message Optional assertion message
     * @return void
     */
    public function assertNull(string $dataKey, string $message = ''): void
    {
        $dataGet = data_get($this->responseData, $dataKey);
        Assert::assertNull($dataGet, $message);
    }

    /**
     * Assert that a given data key is not null.
     *
     * @param string $dataKey The key path in the response data
     * @param string $message Optional assertion message
     * @return void
     */
    public function assertNotNull(string $dataKey, string $message = ''): void
    {
        $dataGet = data_get($this->responseData, $dataKey);
        Assert::assertNotNull($dataGet, $message);
    }

    /**
     * Assert that a given data key is empty.
     *
     * @param string $dataKey The key path in the response data
     * @return void
     */
    public function assertEmpty(string $dataKey): void
    {
        /** @noinspection PhpUnusedLocalVariableInspection */
        $dataGet = data_get($this->responseData, $dataKey);
        Assert::assertEmpty($this->getData());
    }

    /**
     * Assert that a given data key is not empty.
     *
     * @param string $dataKey The key path in the response data
     * @return void
     */
    public function assertNotEmpty(string $dataKey): void
    {
        /** @noinspection PhpUnusedLocalVariableInspection */
        $dataGet = data_get($this->responseData, $dataKey);
        Assert::assertNotEmpty($this->getData());
    }
}
