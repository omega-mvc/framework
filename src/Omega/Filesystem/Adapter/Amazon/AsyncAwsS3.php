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

use Exception;
use RuntimeException;
use AsyncAws\Core\Configuration;
use AsyncAws\SimpleS3\SimpleS3Client;
use Omega\Filesystem\Util\Size;

use function array_key_exists;
use function array_merge;
use function is_array;
use function is_string;
use function rtrim;
use function sprintf;

/**
 * Amazon S3 adapter using the AsyncAws SDK.
 *
 * This class implements the necessary methods to interact with
 * Amazon S3 using the AsyncAws SDK, providing an asynchronous
 * interface for file operations.
 *
 * @extends AbstractAmazonS3<\AsyncAws\SimpleS3\SimpleS3Client>
 *
 * @category    Omega
 * @package     Filesystem
 * @subpackage  Adapter\Amazon
 * @link        https://omegamvc.github.io
 * @author      Adriano Giovannini <agisoftt@gmail.com>
 * @copyright   Copyright (c) 2024 - 2025 Adriano Giovannini (https://omegamvc.github.io)
 * @license     https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version     1.0.0

 */
class AsyncAwsS3 extends AbstractAmazonS3
{
    /**
     * Initializes the AsyncAwsS3 adapter with the provided configuration.
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
     * } $config Configuration options for connecting to S3.
     *            Must include 'bucket', 'key', and 'secret'.
     */
    public function __construct(array $config)
    {
        parent::__construct($config);
    }

    /**
     * {@inheritdoc}
     */
    protected function createClient(array $config): object
    {
        $region     = $config['region'] ?? 'us-west-2';
        $accessKey  = $config['key'];
        $secret     = $config['secret'];
        $token      = $config['token'] ?? null;

        if (
            !is_string($region)
            || !is_string($accessKey)
            || !is_string($secret)
            || (null !== $token && !is_string($token))
        ) {
            throw new RuntimeException('Invalid S3 client credentials provided.');
        }

        return new SimpleS3Client([
            'region'          => $region,
            'accessKeyId'     => $accessKey,
            'accessKeySecret' => $secret,
            'sessionToken'    => $token,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function read(string $key): string|false
    {
        $this->ensureBucketExists();

        try {
            $object = $this->service->getObject([
                'Bucket' => $this->bucket,
                'Key'    => $this->computePath($key),
            ]);

            $this->content[$key]['ContentType'] = $object->getContentType();

            return $object->getBody()->getContentAsString();
        } catch (Exception) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $key, string $content): int|false
    {
        $this->ensureBucketExists();

        $uploadOptions = [];
        if ($this->detectContentType) {
            $uploadOptions['ContentType'] = $this->guessContentType($content);
        }

        try {
            $this->service->upload(
                $this->bucket,
                $this->computePath($key),
                $content,
                $uploadOptions
            );

            return Size::fromContent($content);
        } catch (Exception) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $key): bool
    {
        return $this->service->has($this->bucket, $this->computePath($key));
    }

    /**
     * {@inheritdoc}
     */
    public function mtime(string $key): int|false
    {
        try {
            $result = $this->service->headObject([
                'Bucket' => $this->bucket,
                'Key'    => $this->computePath($key),
            ]);

            $lastModified = $result->getLastModified();

            if (null === $lastModified) {
                return false;
            }

            return $lastModified->getTimestamp();
        } catch (Exception) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function size(string $key): int|false
    {
        try {
            $result = $this->service->headObject([
                'Bucket' => $this->bucket,
                'Key'    => $this->computePath($key),
            ]);

            $contentLength = $result->getContentLength();

            if (null === $contentLength) {
                return false;
            }

            return $contentLength;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function listKeys(string $prefix = ''): array
    {
        $this->ensureBucketExists();

        $options = ['Bucket' => $this->bucket];
        if ($prefix != '') {
            $options['Prefix'] = $this->computePath($prefix);
        } elseif (!empty($this->options['directory']) && is_string($this->options['directory'])) {
            $options['Prefix'] = $this->options['directory'];
        }

        $keys   = [];
        $result = $this->service->listObjectsV2($options);
        foreach ($result->getContents() as $file) {
            $fileKey = $file->getKey();
            if (is_string($fileKey)) {
                $keys[] = $this->computeKey($fileKey);
            }
        }

        return ['keys' => $keys, 'dirs' => []];
    }

    /**
     * {@inheritdoc}
     */
    public function isDirectory(string $key): bool
    {
        $result = $this->service->listObjectsV2([
            'Bucket'  => $this->bucket,
            'Prefix'  => rtrim($this->computePath($key), '/') . '/',
            'MaxKeys' => 1,
        ]);

        return !empty($result->getContents(true));
    }

    /**
     * {@inheritdoc}
     */
    protected function ensureBucketExists(): bool
    {
        if ($this->bucketExists) {
            return true;
        }

        if ($this->bucketExists = $this->service->bucketExists(['Bucket' => $this->bucket])->isSuccess()) {
            return true;
        }

        if (!$this->options['create']) {
            throw new RuntimeException(
                sprintf(
                    'The configured bucket "%s" does not exist.',
                    $this->bucket
                )
            );
        }
        $this->service->createBucket([
            'Bucket'                    => $this->bucket,
            'CreateBucketConfiguration' => [
                'LocationConstraint' => $this->service->getConfiguration()->get(Configuration::OPTION_REGION),
            ],
        ]);
        $this->bucketExists = true;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function mimeType(string $key): string|false
    {
        try {
            $result = $this->service->headObject([
                'Bucket' => $this->bucket,
                'Key'    => $this->computePath($key),
            ]);

            $contentType = $result->getContentType();

            if (null === $contentType) {
                return false;
            }

            return $contentType;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rename(string $sourceKey, string $targetKey): bool
    {
        $this->ensureBucketExists();

        try {
            $this->service->copyObject([
                'Bucket'     => $this->bucket,
                'Key'        => $this->computePath($targetKey),
                'CopySource' => $this->bucket . '/' . $this->computePath($sourceKey),
            ]);

            return $this->delete($sourceKey);
        } catch (Exception) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        try {
            $this->service->deleteObject([
                'Bucket' => $this->bucket,
                'Key'    => $this->computePath($key),
            ]);

            return true;
        } catch (Exception) {
            return false;
        }
    }
}
