<?php

/**
 * Part of Omega - Filesystem Package.
 * php version 8.3
 *
 * @link       https://omegamvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2024 - 2025 Adriano Giovannini (https://omegamvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    1.0.0
 */

declare(strict_types=1);

namespace Omega\Filesystem\Adapter\Amazon;

use finfo;
use LogicException;
use RuntimeException;
use Omega\Filesystem\Adapter\FilesystemAdapterInterface;
use Omega\Filesystem\Contracts\ListKeysAwareInterface;
use Omega\Filesystem\Contracts\MetadataSupporterInterface;
use Omega\Filesystem\Contracts\MimeTypeProviderInterface;
use Omega\Filesystem\Contracts\SizeCalculatorInterface;

use function array_merge;
use function is_string;
use function ltrim;
use function sprintf;
use function strlen;
use function substr;

/**
 * Abstract class for implementing an Amazon S3 adapter.
 *
 * This class serves as a base implementation for an Amazon S3 filesystem adapter.
 * It provides core methods to interact with Amazon S3, such as reading, writing,
 * deleting, and renaming files. It also supports metadata management, size calculation,
 * mime type detection, and listing keys. Specific functionality, such as the S3 client
 * creation, is left to the implementing classes.
 *
 * @template TService of object
 *
 * @category   Omega
 * @package    Filesystem
 * @subpackage Adapter\Amazon
 * @link       https://omegamvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2024 - 2025 Adriano Giovannini (https://omegamvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    1.0.0
 */
abstract class AbstractAmazonS3 implements
    FilesystemAdapterInterface,
    MetadataSupporterInterface,
    ListKeysAwareInterface,
    SizeCalculatorInterface,
    MimeTypeProviderInterface
{
    /**
     * The S3 service client.
     *
     * @var TService Holds the S3 service client.
     */
    protected object $service;

    /**
     * The name of the S3 bucket.
     *
     * @var string Holds the name of the S3 bucket.
     */
    protected string $bucket;

    /**
     * Options for the S3 adapter
     *
     * @var array<string, mixed> Holds the options for the S3 adapter.
     */
    protected array $options = [];

    /**
     * Indicates whether the bucket exists.
     *
     *  @var bool Indicates whether the bucket exists.
     */
    protected bool $bucketExists = false;

    /**
     * Metadata content.
     *
     * @var array<string, array<string, mixed>> Holds the metadata array content.
     */
    protected array $content = [];

    /**
     * Flag for detecting content type.
     *
     * @var bool Flag for detecting content type.
     */
    protected bool $detectContentType;

    /**
     * Constructor.
     *
     * Initializes the adapter with configuration details, including the bucket name,
     * credentials, and optional settings. Throws an exception if the required config
     * parameters are missing.
     *
     * @param array{
     *   bucket: string,
     *   key: string,
     *   secret: string,
     *   region?: string,
     *   token?: string|null,
     *   detectContentType?: bool,
     *   create?: bool,
     *   directory?: string,
     *   acl?: string,
     *   options?: array<string, mixed>,
     * } $config The configuration array with bucket, key, secret, and other options.
     * @return void
     * @throws LogicException If the configuration lacks required parameters.
     */
    public function __construct(array $config)
    {
        if (empty($config['bucket'])) {
            throw new LogicException('The bucket name must be provided in the configuration.');
        }

        if (empty($config['key']) || empty($config['secret'])) {
            throw new LogicException('Both key and secret must be provided in the configuration.');
        }

        $this->bucket            = $config['bucket'];
        $this->detectContentType = $config['detectContentType'] ?? false;

        $this->service = $this->createClient($config);

        foreach ($config['options'] ?? [] as $name => $value) {
            $this->options[$name] = $value;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setMetadata(string $key, array $content): void
    {
        if (isset($content['contentType'])) {
            $content['ContentType'] = $content['contentType'];
            unset($content['contentType']);
        }

        $this->content[$key] = $content;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata(string $key): array
    {
        return $this->content[$key] ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function keys(): array
    {
        $keys = $this->listKeys();

        return $keys['keys'];
    }

    /**
     * Combines and merges global options, key-specific options, and metadata.
     *
     * @param string              $key     The file key.
     * @param array<string, mixed> $options Additional options to be merged.
     * @return array<string, mixed> The merged options.
     */
    protected function getOptions(string $key, array $options = []): array
    {
        $options['ACL']    = $this->options['acl'];
        $options['Bucket'] = $this->bucket;
        $options['Key']    = $this->computePath($key);

        return array_merge($this->options, $options, $this->getMetadata($key));
    }
    /**
     * Computes the full path for the specified key.
     *
     * @param string $key The file key.
     * @return string The computed path.
     */
    protected function computePath(string $key): string
    {
        $directory = $this->options['directory'] ?? '';
        if (!is_string($directory) || '' === $directory) {
            return $key;
        }

        return $directory . '/' . $key;
    }

    /**
     * Computes the key from a given path.
     *
     * @param string $path The full path.
     * @return string The key derived from the path.
     */
    protected function computeKey(string $path): string
    {
        $directory = $this->options['directory'] ?? '';
        if (!is_string($directory)) {
            return $path;
        }

        return ltrim(substr($path, strlen($directory)), '/');
    }

    /**
     * Guesses the content type of a file or resource.
     *
     * @param string $content The content or resource to guess the mime type for.
     * @return string The mime type.
     * @throws RuntimeException if the mime type could not be determined.
     */
    protected function guessContentType(string $content): string
    {
        $fileInfo = new finfo(FILEINFO_MIME_TYPE);

        $mimeType = $fileInfo->buffer($content);

        if (false === $mimeType) {
            throw new RuntimeException('Could not determine the content type.');
        }

        return $mimeType;
    }

    /**
     * {@inheritdoc}
     */
    abstract public function read(string $key): string|false;

    /**
     * {@inheritdoc}
     */
    abstract public function write(string $key, string $content): int|false;

    /**
     * {@inheritdoc}
     */
    abstract public function exists(string $key): bool;

    /**
     * {@inheritdoc}
     */
    abstract public function mtime(string $key): int|false;

    /**
     * {@inheritdoc}
     */
    abstract public function size(string $key): int|false;

    /**
     * {@inheritdoc}
     */
    abstract public function listKeys(string $prefix = ''): array;

    /**
     * {@inheritdoc}
     */
    abstract public function isDirectory(string $key): bool;

    /**
     * Ensures the specified bucket exists.
     *
     * If the bucket does not exist and the create option is set to true,
     * it will try to create the bucket. Throws a RuntimeException if
     * the bucket does not exist and the create option is false.
     *
     * @return bool True if the bucket exists or was successfully created.
     * @throws RuntimeException if the bucket does not exist or could not be created.
     */
    abstract protected function ensureBucketExists(): bool;

    /**
     * {@inheritdoc}
     */
    abstract public function mimeType(string $key): string|false;

    /**
     * Creates a new S3 client instance based on the provided configuration.
     *
     * @param array<string, mixed> $config Configuration options for the S3 client.
     * @return TService An instance of the S3 client.
     */
    abstract protected function createClient(array $config): object;
}
