<?php

/**
 * Part of Omega - Filesystem Package.
 * php version 8.3
 *
 * @link        https://omegamvc.github.io
 * @author      Adriano Giovannini <agisoftt@gmail.com>
 * @copyright   Copyright (c) 2024 - 2025 Adriano Giovannini (https://omegamvc.github.io)
 * @license     https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version     1.0.0
 */

declare(strict_types=1);

namespace Omega\Filesystem\Adapter\Amazon;

use Countable;
use Exception;
use RuntimeException;
use Aws\S3\S3Client;
use Omega\Filesystem\Util\Size;

use function array_merge;
use function count;
use function is_array;
use function rtrim;
use function sprintf;
use function strtotime;

/**
 * Amazon S3 adapter.
 *
 * This class implements the necessary methods to interact with
 * Amazon S3.
 *
 * @extends AbstractAmazonS3<\Aws\S3\S3Client>
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
class AwsS3 extends AbstractAmazonS3
{
    /**
     * Initializes the AWS S3 adapter with the provided configuration.
     *
     * @param array{bucket: string, key: string, secret: string,
     *     region?: string, token?: string|null,
     *     detectContentType?: bool, create?: bool,
     *     directory?: string, acl?: string,
     *     options?: array<string, mixed>} $config
     *     Configuration options for connecting to S3.
     *     Must include 'bucket', 'key', and 'secret'.
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
        return new S3Client([
            'version'     => 'latest',
            'region'      => $config['region'] ?? 'us-west-2',
            'credentials' => [
                'key'    => $config['key'],
                'secret' => $config['secret'],
                'token'  => $config['token'] ?? null,
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function read(string $key): string|false
    {
        $this->ensureBucketExists();
        $options = $this->getOptions($key);

        try {
            $object = $this->service->getObject($options);
            $this->content[$key]['ContentType'] = $object->get('ContentType');

            $body = $object->get('Body');

            if (!is_string($body)) {
                return false;
            }

            return $body;
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
        $options = $this->getOptions($key, ['Body' => $content]);

        /*
         * If the ContentType was not already set in the metadata, then we autodetect
         * it to prevent everything being served up as binary/octet-stream.
         */
        if (!isset($options['ContentType']) && $this->detectContentType) {
            $options['ContentType'] = $this->guessContentType($content);
        }

        try {
            $this->service->putObject($options);

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
        return $this->service->doesObjectExist($this->bucket, $this->computePath($key));
    }

    /**
     * {@inheritdoc}
     */
    public function mtime(string $key): int|false
    {
        try {
            $result = $this->service->headObject($this->getOptions($key));

            $lastModified = $result['LastModified'] ?? null;

            if (!is_string($lastModified)) {
                return false;
            }

            return strtotime($lastModified);
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
            $result = $this->service->headObject($this->getOptions($key));

            $contentLength = $result['ContentLength'] ?? null;

            if (!is_int($contentLength)) {
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
        } elseif (!empty($this->options['directory'])) {
            $options['Prefix'] = $this->options['directory'];
        }

        $keys = [];
        $iter = $this->service->getIterator('ListObjects', $options);
        foreach ($iter as $file) {
            if (!$file instanceof \ArrayAccess) {
                continue;
            }

            $key = $file['Key'] ?? null;
            if (is_string($key)) {
                $keys[] = $this->computeKey($key);
            }
        }

        return ['keys' => $keys, 'dirs' => []];
    }

    /**
     * {@inheritdoc}
     */
    public function isDirectory(string $key): bool
    {
        $result = $this->service->listObjects([
            'Bucket'  => $this->bucket,
            'Prefix'  => rtrim($this->computePath($key), '/') . '/',
            'MaxKeys' => 1,
        ]);
        if (isset($result['Contents'])) {
            if (is_array($result['Contents']) || $result['Contents'] instanceof Countable) {
                return count($result['Contents']) > 0;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function ensureBucketExists(): bool
    {
        if ($this->bucketExists) {
            return true;
        }

        if ($this->bucketExists = $this->service->doesBucketExist($this->bucket)) {
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
            'Bucket'             => $this->bucket,
            'LocationConstraint' => $this->service->getRegion(),
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
            $result = $this->service->headObject($this->getOptions($key));

            $contentType = $result['ContentType'] ?? null;

            if (!is_string($contentType)) {
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

        $options = $this->getOptions(
            $targetKey,
            ['CopySource' => $this->bucket . '/' . $this->computePath($sourceKey)]
        );

        try {
            $this->service->copyObject(array_merge($options, $this->getMetadata($targetKey)));

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
            $this->service->deleteObject($this->getOptions($key));

            return true;
        } catch (Exception) {
            return false;
        }
    }
}
