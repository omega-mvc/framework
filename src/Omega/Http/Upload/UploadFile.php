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
use Omega\Http\Exceptions\MultiFileUploadDetectException;

use function explode;
use function file_get_contents;
use function is_array;
use function is_dir;
use function strtolower;
use function urlencode;

/**
 * Concrete implementation for handling single file uploads.
 *
 * This class specializes {@see AbstractUpload} to support
 * the upload of exactly one file at a time. If a multi-file
 * structure is detected, an exception is thrown.
 *
 * It provides high-level helpers to upload the file to disk
 * and to retrieve its contents once the upload succeeds.
 *
 * @category   Omega
 * @package    Http
 * @subpackage Uploads
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
final class UploadFile extends AbstractUpload
{
    /**
     * Create a new single-file upload handler.
     *
     * Expects a standard `$_FILES`-like array describing a single file.
     * If a multi-file upload structure is detected, a
     * {@see MultiFileUploadDetectException} is thrown.
     *
     * @param array<string, string|int> $files Single-file entry from $_FILES.
     * @return void
     * @throws MultiFileUploadDetectException When multiple files are detected.
     */
    public function __construct(array $files)
    {
        parent::__construct($files);

        if (is_array($files['name'])) {
            throw new MultiFileUploadDetectException();
        }

        /** @noinspection DuplicatedCode */
        $this->fileName[]  = $files['name'];
        $this->fileType[]  = $files['type'];
        $this->fileTmp[]   = $files['tmp_name'];
        $this->fileError[] = $files['error'];
        $this->fileSize[]  = $files['size'];
        // parse files extension
        $extension             = explode('.', $files['name']);
        $this->fileExtension[] = strtolower(end($extension));
    }

    /**
     * Upload the file to the configured destination.
     *
     * The file is validated and then transferred using
     * `move_uploaded_file()` or `copy()` when test mode is enabled.
     *
     * @return string Absolute file path of the uploaded file on success,
     *                or an empty string on failure.
     */
    public function upload(): string
    {
        return $this->stream()[0] ?? '';
    }

    /**
     * Retrieve the contents of the uploaded file.
     *
     * This method can only be called after a successful upload.
     * If the file was not uploaded or does not exist on disk,
     * an exception is thrown.
     *
     * @return string File contents.
     * @throws FileNotUploadedException If the upload was not successful.
     * @throws FileNotExistsException   If the uploaded file cannot be found.
     */
    public function get(): string
    {
        $destination =  $this->uploadLocation . $this->uploadName . '.' . $this->fileExtension[0];

        if (!$this->success) {
            throw new FileNotUploadedException();
        }

        $content = file_get_contents($destination);
        if (false === $content) {
            throw new FileNotExistsException($destination);
        }

        return $content;
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
