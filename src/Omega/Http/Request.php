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

/** @noinspection PhpGetterAndSetterCanBeReplacedWithPropertyHooksInspection */

declare(strict_types=1);

namespace Omega\Http;

use ArrayAccess;
use ArrayIterator;
use Closure;
use Exception;
use IteratorAggregate;
use Omega\Collection\Collection;
use Omega\Collection\CollectionImmutable;
use Omega\Http\Upload\UploadFile;
use Omega\Macroable\MacroableTrait;
use Omega\Text\Str;
use Omega\Validator\Validator;
use ReturnTypeWillChange;
use Traversable;

use function array_merge;
use function func_num_args;
use function get_debug_type;
use function in_array;
use function is_array;
use function json_decode;
use function sprintf;
use function strcasecmp;
use function strtoupper;
use function substr;

use const JSON_BIGINT_AS_STRING;
use const JSON_THROW_ON_ERROR;

/**
 * Represents an HTTP request.
 *
 * This class encapsulates all aspects of a client request, including:
 * - Request method (GET, POST, etc.)
 * - URL and query parameters
 * - POST data
 * - Uploaded files
 * - Cookies and headers
 * - Raw body and JSON content
 * - Custom attributes
 *
 * Implements ArrayAccess and IteratorAggregate to allow easy access to
 * input data and iteration over request parameters.
 *
 * @category  Omega
 * @package   Http
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 *
 * @method Validator  validate(?Closure $rule = null, ?Closure $filter = null)
 * @method UploadFile upload(array|string $fileName)
 *
 * @implements ArrayAccess<string, string>
 * @implements IteratorAggregate<string, string>
 *
 * @property string|null $query_1
 */
class Request implements ArrayAccess, IteratorAggregate
{
    use MacroableTrait;

    /** @var string The HTTP request method (GET, POST, etc.). */
    private string $method;

    /** @var string The requested URL. */
    private string $url;

    /** @var Collection<string, string> Query parameters ($_GET). */
    private Collection $query;

    /** @var array<string, string|int|bool> Custom attributes associated with the request. */
    private array $attributes;

    /** @var Collection<string, string> POST parameters ($_POST). */
    private Collection $post;

    /** @var array<string, array<int, string>|string> Uploaded files ($_FILES). */
    private array $files;

    /** @var array<string, string> Cookies ($_COOKIE). */
    private array $cookies;

    /** @var array<string, string> HTTP headers associated with the request. */
    private array $headers;

    /** @var string Remote IP address of the client making the request. */
    private string $remoteAddress;

    /** @var string|null Raw body content of the request. */
    private ?string $rawBody;

    /** @var Collection<string, string> Parsed JSON content from the request body. */
    private Collection $json;

    /** @var array<string, string[]> MIME types associated with known formats. */
    protected array $formats = [
        'html'   => ['text/html', 'application/xhtml+xml'],
        'txt'    => ['text/plain'],
        'js'     => ['application/javascript', 'application/x-javascript', 'text/javascript'],
        'css'    => ['text/css'],
        'json'   => ['application/json', 'application/x-json'],
        'jsonld' => ['application/ld+json'],
        'xml'    => ['text/xml', 'application/xml', 'application/x-xml'],
        'rdf'    => ['application/rdf+xml'],
        'atom'   => ['application/atom+xml'],
        'rss'    => ['application/rss+xml'],
        'form'   => ['application/x-www-form-urlencoded', 'multipart/form-data'],
    ];

    /**
     * Initialize a new HTTP request.
     *
     * @param string                                         $url           The requested URL.
     * @param array<string, string>                          $query         Query parameters ($_GET).
     * @param array<string, string>                          $post          POST parameters ($_POST).
     * @param array<string, string>                          $attributes    Custom attributes.
     * @param array<string, string>                          $cookies       Cookies ($_COOKIE).
     * @param array<string, string|array<string,int|string>> $files         Uploaded files ($_FILES)
     * @param array<string, string>                          $headers       HTTP headers.
     * @param string                                         $method        HTTP method, default 'GET'.
     * @param string                                         $remoteAddress Client IP address, default '::1'.
     * @param string|null                                    $rawBody       Raw request body.
     * @return void
     */
    public function __construct(
        string $url,
        array $query = [],
        array $post = [],
        array $attributes = [],
        array $cookies = [],
        array $files = [],
        array $headers = [],
        string $method = 'GET',
        string $remoteAddress = '::1',
        ?string $rawBody = null,
    ) {
        $this->initialize(
            $url,
            $query,
            $post,
            $attributes,
            $cookies,
            $files,
            $headers,
            $method,
            $remoteAddress,
            $rawBody
        );
    }

    /**
     * Initialize request data explicitly.
     *
     * This method allows re-initializing all request data after construction.
     *
     * @param string                                         $url           The requested URL.
     * @param array<string, string>                          $query         Query parameters ($_GET).
     * @param array<string, string>                          $post          POST parameters ($_POST).
     * @param array<string, string>                          $attributes    Custom attributes.
     * @param array<string, string>                          $cookies       Cookies ($_COOKIE).
     * @param array<string, string|array<string,int|string>> $files         Uploaded files ($_FILES)
     * @param array<string, string>                          $headers       HTTP headers.
     * @param string                                         $method        HTTP method, default 'GET'.
     * @param string                                         $remoteAddress Client IP address, default '::1'.
     * @param string|null                                    $rawBody       Raw request body.
     * @return self Returns the request instance.
     */
    public function initialize(
        string $url,
        array $query = [],
        array $post = [],
        array $attributes = [],
        array $cookies = [],
        array $files = [],
        array $headers = [],
        string $method = 'GET',
        string $remoteAddress = '::1',
        ?string $rawBody = null,
    ): self {
        $this->url             = $url;
        $this->query           = new Collection($query);
        $this->post            = new Collection($post);
        $this->attributes      = $attributes;
        $this->cookies         = $cookies;
        $this->files           = $files;
        $this->headers         = $headers;
        $this->method          = $method;
        $this->remoteAddress   = $remoteAddress;
        $this->rawBody         = $rawBody;

        return $this;
    }

    /**
     * Create a duplicate of the current request, optionally overriding
     * query, post, attributes, cookies, files, or headers.
     *
     * @param array<string, string>|null $query Optional query parameters to override.
     * @param array<string, string>|null $post Optional POST parameters to override.
     * @param array<string, string>|null $attributes Optional custom attributes to override.
     * @param array<string, string>|null $cookies Optional cookies to override.
     * @param array<string, string>|null $files Optional uploaded files to override.
     * @param array<string, string>|null $headers Optional headers to override.
     * @return self Returns a cloned request instance with optional overrides.
     */
    public function duplicate(
        ?array $query = null,
        ?array $post = null,
        ?array $attributes = null,
        ?array $cookies = null,
        ?array $files = null,
        ?array $headers = null,
    ): self {
        $duplicate = clone $this;

        if (null !== $query) {
            $duplicate->query = new Collection($query);
        }
        if (null !== $post) {
            $duplicate->post = new Collection($post);
        }
        if (null !== $attributes) {
            $duplicate->attributes = $attributes;
        }
        if (null !== $cookies) {
            $duplicate->cookies = $cookies;
        }
        if (null !== $files) {
            $duplicate->files = $files;
        }
        if (null !== $headers) {
            $duplicate->headers = $headers;
        }

        return $duplicate;
    }

    /**
     * Clone the current request instance.
     *
     * This method ensures that all internal collections and arrays are deeply cloned
     * so that changes to the cloned instance do not affect the original.
     *
     * @return void
     */
    public function __clone(): void
    {
        $this->query      = clone $this->query;
        $this->post       = clone $this->post;
        // cloning as array
        $this->attributes = new Collection($this->attributes)->all();
        $this->cookies    = new Collection($this->cookies)->all();
        $this->files      = new Collection($this->files)->all();
        $this->headers    = new Collection($this->headers)->all();
    }

    /**
     * Get the requested URL.
     *
     * @return string Returns the full request URL.
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Get the query parameters ($_GET) as an immutable collection.
     *
     * @return CollectionImmutable<string, string> Immutable collection of query parameters.
     */
    public function query(): CollectionImmutable
    {
        return $this->query->immutable();
    }

    /**
     * Get query parameters ($_GET).
     *
     * If a key is provided, returns the value of that parameter.
     * If no key is provided, returns all query parameters as an array.
     *
     * @param string|null $key Optional parameter key to retrieve.
     * @return array<string, string>|string Returns the value of a query parameter or all query parameters as an array.
     */
    public function getQuery(?string $key = null): array|string
    {
        if (func_num_args() === 0) {
            return $this->query->all();
        }

        return $this->query->get($key);
    }

    /**
     * Get POST parameters ($_POST) as an immutable collection.
     *
     * @return CollectionImmutable<string, string> Immutable collection of POST parameters.
     */
    public function post(): CollectionImmutable
    {
        return $this->post->immutable();
    }

    /**
     * Get POST parameters ($_POST).
     *
     * If a key is provided, returns the value of that POST parameter.
     * If no key is provided, returns all POST parameters as an array.
     *
     * @param string|null $key Optional parameter key to retrieve.
     * @return array<string, string>|string Returns the value of a POST parameter or all POST parameters as an array.
     */
    public function getPost(?string $key = null): array|string
    {
        if (func_num_args() === 0) {
            return $this->post->all();
        }

        return $this->post->get($key);
    }

    /**
     * Get uploaded files ($_FILES).
     *
     * If a key is provided, returns the file(s) associated with that key.
     * If no key is provided, returns all uploaded files as an array.
     *
     * @param string|null $key Optional key of the file input to retrieve.
     * @return array<string, array<int, string>|string>|array<int, string>|string
     *         Returns the requested file, an array of files, or all files.
     */
    public function getFile(?string $key = null): array|string
    {
        if (func_num_args() === 0) {
            return $this->files;
        }

        return $this->files[$key];
    }

    /**
     * Get a specific cookie by its key.
     *
     * @param string $key The name of the cookie to retrieve.
     *
     * @return string|null Returns the cookie value if it exists, otherwise null.
     */
    public function getCookie(string $key): ?string
    {
        return $this->cookies[$key] ?? null;
    }

    /**
     * Get all cookies.
     *
     * @return array<string, string>|null Returns an associative array of all cookies, or null if no cookies are set.
     */
    public function getCookies(): ?array
    {
        return $this->cookies;
    }

    /**
     * Get the HTTP method of the request.
     *
     * @return string Returns the request method in uppercase (e.g., GET, POST, PUT, DELETE).
     */
    public function getMethod(): string
    {
        return strtoupper($this->method);
    }

    /**
     * Check if the request method matches the given method.
     *
     * @param string $method The HTTP method to compare against.
     * @return bool Returns true if the request method matches the given method (case-insensitive), false otherwise.
     */
    public function isMethod(string $method): bool
    {
        return strcasecmp($this->method, $method) === 0;
    }

    /**
     * Get one or all request headers.
     *
     * @param string|null $header Optional header key to retrieve. If null, returns all headers.
     * @return array<string, string>|string|null Returns the value of a single header if key is provided,
     *                                           an associative array of all headers if no key is provided,
     *                                           or null if the requested header does not exist.
     */
    public function getHeaders(?string $header = null): array|string|null
    {
        if ($header === null) {
            return $this->headers;
        }

        return $this->headers[$header] ?? null;
    }

    /**
     * Get the MIME types associated with a given format.
     *
     * @param string $format The format name (e.g., "json", "html", "xml").
     * @return string[] Returns an array of MIME types associated with the format.
     */
    public function getMimeTypes(string $format): array
    {
        return $this->formats[$format] ?? [];
    }

    /**
     * Get the format associated with a given MIME type.
     *
     * @param string|null $mimeType The MIME type to check (e.g., "application/json").
     * @return string|null Returns the format name if a matching MIME type is found, otherwise null.
     */
    public function getFormat(?string $mimeType): ?string
    {
        /** @noinspection PhpLoopCanBeConvertedToArrayFindKeyInspection */
        foreach ($this->formats as $format => $mimeTypes) {
            if (in_array($mimeType, $mimeTypes)) {
                return $format;
            }
        }

        return null;
    }

    /**
     * Get the request format based on the "Content-Type" header.
     *
     * @return string|null Returns the format name (e.g., "json", "html") if detected, otherwise null.
     */
    public function getRequestFormat(): ?string
    {
        $content_type = $this->getHeaders('content-type');

        return $this->getFormat($content_type);
    }

    /**
     * Check if a specific header matches the given value.
     *
     * @param string $headerKey The header name to check.
     * @param string $headerVal The expected header value.
     * @return bool Returns true if the header exists and its value matches the given value, false otherwise.
     */
    public function isHeader(string $headerKey, string $headerVal): bool
    {
        if (isset($this->headers[$headerKey])) {
            return $this->headers[$headerKey] === $headerVal;
        }

        return false;
    }

    /**
     * Check if a specific header exists.
     *
     * @param string $header_key The header name to check.
     * @return bool Returns true if the header exists in the request, false otherwise.
     */
    public function hasHeader(string $header_key): bool
    {
        return isset($this->headers[$header_key]);
    }

    /**
     * Determine if the request is made over HTTPS.
     *
     * @return bool Returns true if the request is secured with HTTPS, false otherwise.
     */
    public function isSecured(): bool
    {
        return !empty($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'], 'off');  // http;
    }

    /**
     * Get the remote IP address of the client making the request.
     *
     * @return string Returns the remote IP address.
     */
    public function getRemoteAddress(): string
    {
        return $this->remoteAddress;
    }

    /**
     * Get the raw body content of the request.
     *
     * @return string|null Returns the raw request body, or null if the body is empty.
     */
    public function getRawBody(): ?string
    {
        return $this->rawBody;
    }

    /**
     * Get the JSON body of the request decoded as an array.
     *
     * @return array Returns the request body as an associative array.
     * @throws Exception Throws if the body is empty, cannot be decoded, or does not decode to an array.
     */
    public function getJsonBody(): array
    {
        if ('' === $content = $this->rawBody) {
            throw new Exception(
                'Request body is empty.'
            );
        }

        try {
            $content = json_decode(
                $content,
                true,
                512,
                JSON_BIGINT_AS_STRING | JSON_THROW_ON_ERROR
            );
        } catch (Exception $e) {
            throw new Exception(
                'Could not decode request body.',
                $e->getCode(),
                $e
            );
        }

        if (!is_array($content)) {
            throw new Exception(
                sprintf(
                    'JSON content was expected to decode to an array, "%s" returned.',
                    get_debug_type($content)
                )
            );
        }

        return $content;
    }

    /**
     * Get a custom attribute from the request.
     *
     * @param string $key The attribute name.
     * @param string|int|bool $default Default value to return if the attribute does not exist.
     * @return string|int|bool Returns the attribute value if it exists, otherwise returns the default value.
     */
    public function getAttribute(string $key, string|int|bool $default): string|int|bool
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * Push custom attributes into the request.
     *
     * @param array<string, string|int|bool> $pushAttributes Associative array of attributes to add to the request.
     * @return self Returns the current request instance with the added attributes.
     */
    public function with(array $pushAttributes): self
    {
        $this->attributes = array_merge($this->attributes, $pushAttributes);

        return $this;
    }

    /**
     * Get all request data as an associative array.
     *
     * @return array<string, mixed> Returns all request data including headers, query, post,
     *         attributes, cookies, files, raw body, and method.
     * @throws Exception Throws if an error occurs while retrieving input data.
     */
    public function all(): array
    {
        /** @var Collection<string, string> $input */
        $input = $this->input();

        return array_merge(
            $this->headers,
            $input->toArray(),
            $this->attributes,
            $this->cookies,
            [
                'x-raw'     => $this->getRawBody() ?? '',
                'x-method'  => $this->getMethod(),
                'files'     => $this->files,
            ]
        );
    }

    /**
     * Get all request data wrapped in a single-element array.
     *
     * @return array<int, array<string, mixed>> Returns a single-element array containing all request data.
     * @throws Exception Throws if an error occurs while retrieving input data.
     */
    public function wrap(): array
    {
        return [$this->all()];
    }

    /**
     * Determine if the request is an AJAX request.
     *
     * @return bool Returns true if the "X-Requested-With" header is "XMLHttpRequest", false otherwise.
     */
    public function isAjax(): bool
    {
        return $this->getHeaders('X-Requested-With') == 'XMLHttpRequest';
    }

    /**
     * Determine if the request content type is JSON.
     *
     * @return bool Returns true if the "Content-Type" header contains "/json" or "+json", false otherwise.
     */
    public function isJson(): bool
    {
        /** @var string $contentType */
        $contentType = $this->getHeaders('content-type') ?? '';

        return Str::contains($contentType, '/json') || Str::contains($contentType, '+json');
    }

    /**
     * Get the request JSON body as a Collection.
     *
     * @return Collection<string, string> Returns the JSON body wrapped in a Collection.
     * @throws Exception Throws if the request body is empty or cannot be parsed as JSON.
     */
    public function json(): Collection
    {
        if (false === isset($this->json)) {
            $jsonBody = [];
            foreach ($this->getJsonBody() as $key => $value) {
                $jsonBody[(string) $key] = (string) $value;
            }
            $this->json = new Collection($jsonBody);
        }

        return $this->json;
    }

    /**
     * Get the Authorization header value.
     *
     * @return string|null Returns the value of the "Authorization" header or null if not present.
     */
    public function getAuthorization(): ?string
    {
        return $this->getHeaders('Authorization');
    }

    /**
     * Get the Bearer token from the Authorization header.
     *
     * @return string|null Returns the Bearer token string or null if the header
     *         is missing or does not contain a Bearer token.
     */
    public function getBearerToken(): ?string
    {
        $authorization = $this->getAuthorization();
        if (null === $authorization) {
            return null;
        }

        if (Str::startsWith($authorization, 'Bearer ')) {
            return substr($authorization, 7);
        }

        return null;
    }

    /**
     * Get request input, optionally by key.
     *
     * @template TGetDefault
     * @param string|null $key The input key to retrieve. If null, returns all input.
     * @param TGetDefault $default Default value to return if the input key is not found.
     * @return Collection<string, string>|string|TGetDefault Returns the input as a Collection, a single string value,
     *         or the default value.
     * @throws Exception Throws if an error occurs while retrieving input.
     * @noinspection PhpMissingParamTypeInspection
     */
    public function input(?string $key = null, $default = null): Collection|string
    {
        $input = $this->source()->add($this->query->all());
        if (null === $key) {
            return $input;
        }

        return $input->get($key, $default);
    }

    /**
     * Get the input source based on request method type.
     *
     * @return Collection<string, string> Returns a Collection of the request input depending
     *         on method (query, post, or JSON body).
     * @throws Exception Throws if an error occurs while retrieving input data.
     */
    private function source(): Collection
    {
        if ($this->isJson()) {
            return $this->json();
        }

        return in_array($this->method, ['GET', 'HEAD']) ? $this->query : $this->post;
    }

    /**
     * Determine if the given offset exists in the input source.
     *
     * @param string $offset The input key to check.
     * @return bool Returns true if the key exists, false otherwise.
     * @throws Exception Throws if an error occurs while retrieving input.
     */
    public function offsetExists($offset): bool
    {
        return $this->source()->has($offset);
    }

    /**
     * Get the value at the given offset from the input source.
     *
     * @param string $offset The input key to retrieve.
     * @return string|null Returns the value associated with the key, or null if not found.
     * @throws Exception Throws if an error occurs while retrieving input.
     */
    #[ReturnTypeWillChange]
    public function offsetGet(mixed $offset): ?string
    {
        return $this->__get($offset);
    }

    /**
     * Set a value at the given offset in the input source.
     *
     * @param string $offset The input key to set.
     * @param string $value The value to assign.
     * @return void
     * @throws Exception Throws if an error occurs while setting input.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->source()->set($offset, $value);
    }

    /**
     * Remove a value at the given offset from the input source.
     *
     * @param string $offset The input key to remove.
     * @return void
     * @throws Exception Throws if an error occurs while removing input.
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->source()->remove($offset);
    }

    /**
     * Magic method to get an input element by key.
     *
     * @param string $key The input key to retrieve.
     * @return string|null Returns the value of the key or null if it does not exist.
     * @throws Exception Throws if an error occurs while retrieving input.
     */
    public function __get(string $key): ?string
    {
        return $this->all()[$key] ?? null;
    }

    /**
     * Get an iterator for the input source.
     *
     * @return Traversable Returns an iterator for all input data.
     * @throws Exception Throws if an error occurs while retrieving input.
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->source()->all());
    }
}
