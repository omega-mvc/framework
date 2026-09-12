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

namespace Omega\Http\Upload;

use Omega\Http\Exceptions\FileNotExistsException;
use Omega\Http\Exceptions\FileNotUploadedException;
use Omega\Http\Exceptions\FolderNotExistsException;

use function array_filter;
use function array_values;
use function explode;
use function file_get_contents;
use function is_array;
use function is_dir;
use function is_int;
use function is_string;
use function strtolower;
use function urlencode;

/**
 * Concrete implementation for handling multiple file uploads.
 *
 * This class extends {@see AbstractUpload} to support uploading
 * multiple files in a single request. It normalizes both single
 * and multi-file `$_FILES` structures into a unified internal format
 * and assigns an indexed suffix to each uploaded file.
 *
 * It provides helpers to upload all files and retrieve their
 * contents after a successful upload.
 *
 * @category   Omega
 * @package    Http
 * @subpackage Uploads
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
final class UploadMultiFile extends AbstractUpload
{
    /**
     * Create a new multi-file upload handler.
     *
     * Accepts both single-file and multi-file `$_FILES`-like arrays
     * and normalizes them internally as a multi-file upload.
     *
     * @param array<string, string|int|array<int, string>|array<int, int>> $files Entry from $_FILES.
     * @return void
     */
    public function __construct(array $files)
    {
        parent::__construct($files);

        $name    = $files['name'];
        $type    = $files['type'];
        $tmpName = $files['tmp_name'];
        $error   = $files['error'];
        $size    = $files['size'];

        if (is_array($name) && is_array($type) && is_array($tmpName) && is_array($error) && is_array($size)) {
            $this->fileName  = array_values(array_filter($name, 'is_string'));
            $this->fileType  = array_values(array_filter($type, 'is_string'));
            $this->fileTmp   = array_values(array_filter($tmpName, 'is_string'));
            $this->fileError = array_values(array_filter($error, 'is_int'));
            $this->fileSize  = array_values(array_filter($size, 'is_int'));
            // parse file extension
            foreach ($name as $nameValue) {
                if (!is_string($nameValue)) {
                    continue;
                }

                $extension             = explode('.', $nameValue);
                $this->fileExtension[] = strtolower(end($extension));
            }
        } elseif (
            is_string($name)
            && is_string($type)
            && is_string($tmpName)
            && is_int($error)
            && is_int($size)
        ) {
            $this->fileName[]  = $name;
            $this->fileType[]  = $type;
            $this->fileTmp[]   = $tmpName;
            $this->fileError[] = $error;
            $this->fileSize[]  = $size;
            // parse files extension
            $extension             = explode('.', $name);
            $this->fileExtension[] = strtolower(end($extension));
        } else {
            $this->fileName       = [];
            $this->fileType       = [];
            $this->fileTmp        = [];
            $this->fileError      = [];
            $this->fileSize       = [];
            $this->fileExtension  = [];
        }

        $this->isMulti = true;
    }

    /**
     * Upload all files to the configured destination.
     *
     * Each file is validated and then transferred using
     * `move_uploaded_file()` or `copy()` when test mode is enabled.
     * Uploaded files are suffixed with an incremental index.
     *
     * @return string[] List of absolute file paths for successfully
     *                  uploaded files. An empty array is returned on failure.
     */
    public function uploads(): array
    {
        return $this->stream();
    }

    /**
     * Retrieve the contents of all uploaded files.
     *
     * This method can only be called after a successful upload.
     * If any uploaded file cannot be found or read, an exception
     * is thrown.
     *
     * @return string[] Contents of all uploaded files, in upload order.
     *
     * @throws FileNotUploadedException If the upload was not successful.
     * @throws FileNotExistsException   If any uploaded file cannot be found.
     */
    public function getAll(): array
    {
        if (!$this->success) {
            throw new FileNotUploadedException();
        }

        $contents = [];

        foreach ($this->fileExtension as $key => $extension) {
            $destination    = $this->uploadLocation . $this->uploadName . $key . '.' . $extension;
            $content        = file_get_contents($destination);

            if (false === $content) {
                throw new FileNotExistsException($destination);
            }
            $contents[] = $content;
        }

        return $contents;
    }

    /**
     * {@inheritDoc}
     */
    public function setFileName(string $fileName): self
    {
        // file name without extension
        $fileName         = urlencode($fileName);
        $this->uploadName = $fileName;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setFolderLocation(string $folderLocation): self
    {
        if (!is_dir($folderLocation)) {
            throw new FolderNotExistsException($folderLocation);
        }

        $this->uploadLocation = $folderLocation;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setFileTypes(array $extensions): self
    {
        $this->uploadTypes = $extensions;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setMimeTypes(array $mimes): self
    {
        $this->uploadMime = $mimes;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setMaxFileSize(int $byte): self
    {
        $this->uploadSizeMax = $byte;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function markTest(bool $markUploadTest): self
    {
        $this->test = $markUploadTest;

        return $this;
    }
}
