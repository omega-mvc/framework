<?php

/**
 * Part of Omega - Http Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Http;

use ArrayObject;
use Exception;
use InvalidArgumentException;

use function json_decode;
use function json_encode;

use const JSON_HEX_AMP;
use const JSON_HEX_APOS;
use const JSON_HEX_QUOT;
use const JSON_HEX_TAG;

/**
 * HTTP response specialized for JSON payloads.
 *
 * This response automatically encodes data to JSON, sets the
 * appropriate `Content-Type` header, and manages JSON encoding
 * options.
 *
 * The response body is always stored internally as a JSON string.
 * Any change to the data or encoding options will re-encode the
 * payload and update the response content accordingly.
 *
 * @category  Omega
 * @package   Http
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class JsonResponse extends Response
{
    /**
     * JSON-encoded response payload.
     *
     * @var string
     */
    protected string $data;

    /**
     * JSON encoding options bitmask.
     *
     * @var int
     */
    protected int $encoding_options = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;

    /**
     * Create a new JSON response instance.
     *
     * If no data is provided, an empty JSON object is used.
     * The payload is automatically encoded and prepared
     * as the response body.
     *
     * @param array|null            $data       Data to be JSON-encoded.
     * @param int                   $statusCode HTTP status code.
     * @param array<string, string> $headers    Additional HTTP headers.
     * @return void
     * @throws Exception When JSON encoding fails.
     */
    public function __construct(?array $data = null, int $statusCode = 200, array $headers = [])
    {
        parent::__construct('', $statusCode, $headers);
        $data ??= new ArrayObject();
        $this->setData($data);
    }

    /**
     * Set JSON encoding options.
     *
     * Changing the encoding options will re-encode the current
     * response data using the new options.
     *
     * @param int $encodingOptions JSON encoding options bitmask.
     * @return self
     * @throws Exception When re-encoding the current data fails.
     */
    public function setEncodingOptions(int $encodingOptions): self
    {
        $this->encoding_options = $encodingOptions;
        $this->setData(json_decode($this->data));

        return $this;
    }

    /**
     * Get the current JSON encoding options.
     *
     * @return int JSON encoding options bitmask.
     */
    public function getEncodingOptions(): int
    {
        return $this->encoding_options;
    }

    /**
     * Set the response body using a raw JSON string.
     *
     * The provided JSON string is assumed to be valid and will
     * be used directly as the response content.
     *
     * @param string $json Raw JSON string.
     * @return self
     */
    public function setJson(string $json): self
    {
        $this->data = $json;
        $this->prepare();

        return $this;
    }

    /**
     * Set the response data and encode it as JSON.
     *
     * The data will be encoded using the current encoding options.
     * The response content and headers are updated automatically.
     *
     * @param mixed $data Data to encode as JSON.
     * @return self
     * @throws InvalidArgumentException When JSON encoding fails.
     */
    public function setData(mixed $data): self
    {
        if (false === ($json = json_encode($data, $this->encoding_options))) {
            throw new InvalidArgumentException('Invalid encode data.');
        }

        $this->data = $json;
        $this->prepare();

        return $this;
    }

    /**
     * Get the decoded JSON response data.
     *
     * @return array Decoded JSON data as an associative array.
     */
    public function getData(): array
    {
        return json_decode($this->data, true);
    }

    /**
     * Prepare the HTTP response for JSON output.
     *
     * Sets the `Content-Type` header to `application/json`
     * and updates the response body with the encoded payload.
     *
     * @return void
     */
    protected function prepare(): void
    {
        $this->setContentType('application/json');
        $this->setContent($this->data);
    }
}
