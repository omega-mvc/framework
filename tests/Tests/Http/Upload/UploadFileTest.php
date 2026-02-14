<?php

/**
 * Part of Omega - Tests\Http Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Http\Upload;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Omega\Http\Exceptions\FolderNotExistsException;
use Omega\Http\Upload\UploadFile;
use Omega\Http\Upload\UploadMultiFile;
use Tests\FixturesPathTrait;

use function file_exists;
use function filesize;
use function filetype;
use function ini_get;
use function trim;
use function unlink;

/**
 * Integration test suite for UploadFile and UploadMultiFile.
 *
 * Verifies single and multiple file uploads, validation rules
 * (type, size, mime, folder), and error handling scenarios
 * using real fixture files and filesystem interactions.
 *
 * @category   Tests
 * @package    Http
 * @subpackage Upload
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[CoversClass(FolderNotExistsException::class)]
#[CoversClass(UploadFile::class)]
#[CoversClass(UploadMultiFile::class)]
final class UploadFileTest extends TestCase
{
    use FixturesPathTrait;

    /** @var array<string, mixed> Mocked $_FILES-like structure used in tests */
    private array $files = [];

    /** Handles single file upload configuration and execution */
    private UploadFile $upload;

    /**
     * Sets up the environment before each test method.
     *
     * This method is called automatically by PHPUnit before each test runs.
     * It is responsible for initializing the application instance, setting up
     * dependencies, and preparing any state required by the test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        if (!ini_get('file_uploads')) {
            $this->markTestSkipped('file_uploads is disabled in php.ini');
        }

        $this->files = $this->getFiles();

        $this->files['file_1']['size'] = filesize($this->files['file_1']['tmp_name']);
        $this->files['file_1']['type'] = filetype($this->files['file_1']['tmp_name']);

        $this->upload = new UploadFile($this->files['file_1']);
        $this->upload
            ->markTest(true)
            ->setFileName('success')
            ->setFileTypes(['txt', 'md'])
            ->setFolderLocation($this->setFixturePath(slash(path: '/fixtures/application-read/upload/')))
            ->setMaxFileSize(91)
            ->setMimeTypes(['file']);
    }

    /**
     * Tears down the environment after each test method.
     *
     * This method is called automatically by PHPUnit after each test runs.
     * It is responsible for cleaning up resources, flushing the application
     * state, unsetting properties, and resetting any static or global state
     * to avoid side effects between tests.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $file = $this->setFixturePath(slash(path: '/fixtures/application-read/upload/success.txt'));
        if (file_exists($file)) {
            unlink($file);
        }
    }

    /**
     * Returns the array of files with correct dynamic paths.
     *
     * @return array<string, mixed> The files data array mimicking $_FILES structure
     */
    private function getFiles(): array
    {
        return [
            'file_1' => [
                'name'     => 'test123.txt',
                'type'     => 'file',
                'tmp_name' => $this->setFixturePath(
                    slash(path: '/fixtures/application-read/upload/test123.tmp')
                ),
                'error'    => 0,
                'size'     => 1,
            ],
            'file_2' => [
                'name'     => ['test123.txt', 'test234.txt'],
                'type'     => ['file', 'file'],
                'tmp_name' => [
                    $this->setFixturePath(slash(path: '/fixtures/application-read/upload/test123.tmp')),
                    $this->setFixturePath(slash(path: '/fixtures/application-read/upload/test234.tmp')),
                ],
                'error'    => [0, 0],
                'size'     => [1, 1],
            ],
        ];
    }

    /**
     * Test it can upload file valid.
     *
     * @return void
     */
    public function testItCanUploadFileValid(): void
    {
        $this->upload->upload();

        $this->assertTrue($this->upload->success());
        $this->assertEquals('success', $this->upload->getError());
        $this->assertEquals(
            'This is a story about something that happened long ago when your grandfather was a child.',
            trim($this->upload->get()
            )
        );
    }

    /**
     * Test it can upload file invalid file type.
     *
     * @return void
     */
    public function testItCanUploadFileInvalidFileType(): void
    {
        $this->upload->setFileTypes(['md'])->upload();

        $this->assertFalse($this->upload->success());
    }

    /** @test */
    public function testItCanUploadFileInvalidFileFolder()
    {
        $this->expectException(FolderNotExistsException::class);

        $this->upload->setFolderLocation('/unknown');
    }

    /**
     * Test it can upload file invalid file size.
     *
     * @return void
     */
    public function testItCanUploadFileInvalidFileSize(): void
    {
        $this->upload->setMaxFileSize(89)->upload();

        $this->assertFalse($this->upload->success());
    }

    /**
     * Test it can upload file invalid mime.
     *
     * @return void
     */
    public function testItCanUploadFileInvalidMime(): void
    {
        $this->upload->setMimeTypes(['image/jpeg'])->upload();

        $this->assertFalse($this->upload->success());
    }

    /**
     * Test it can upload invalid no file upload.
     *
     * @return void
     */
    public function testItCanUploadFileInvalidNoFileUpload(): void
    {
        $this->files['file_1']['error'] = 4;

        $upload = new UploadFile($this->files['file_1']);
        $upload
            ->markTest(true)
            ->setFileName('success')
            ->setFileTypes(['txt', 'md'])
            ->setFolderLocation($this->setFixturePath(slash(path: '/fixtures/application-read/upload/')))
            ->setMaxFileSize(91)
            ->setMimeTypes(['file']);

        $this->assertFalse($upload->success());

        // reset
        $this->files['file_1']['error'] = 0;
    }

    /**
     * Test it can multi upload file but single file.
     *
     * @return void
     */
    public function testItCanMultiUploadFileButSingleFile(): void
    {
        $upload = new UploadMultiFile($this->files['file_2']);
        $upload
            ->markTest(true)
            ->setFileName('multi_file_')
            ->setFileTypes(['txt', 'md'])
            ->setFolderLocation($this->setFixturePath(slash(path: '/fixtures/application-read/upload/')))
            ->setMaxFileSize(91)
            ->setMimeTypes(['file'])
            ->uploads();

        $this->assertTrue($upload->success());
        $this->assertFileExists($this->setFixturePath(slash(path: '/fixtures/application-read/upload/multi_file_0.txt')));
        $this->assertFileExists($this->setFixturePath(slash(path: '/fixtures/application-read/upload/multi_file_1.txt')));

        unlink($this->setFixturePath(slash(path: '/fixtures/application-read/upload/multi_file_0.txt')));
        unlink($this->setFixturePath(slash(path: '/fixtures/application-read/upload/multi_file_1.txt')));
    }

    public function itCanMultiUploadFile(): void
    {
        $upload = new UploadMultiFile($this->files['file_2']);
        $upload
            ->markTest(true)
            ->setFileName('multi_file')
            ->setFileTypes(['txt', 'md'])
            ->setFolderLocation($this->setFixturePath(slash(path: '/fixtures/application-read/upload/')))
            ->setMaxFileSize(91)
            ->setMimeTypes(['file'])
            ->uploads();

        $this->assertTrue($upload->success());
        $this->assertFileExists($this->setFixturePath(slash(path: '/fixtures/application-read/upload/multi_file_0.txt')));
        $this->assertFileExists($this->setFixturePath(slash(path: '/fixtures/application-read/upload/multi_file_1.txt')));

        unlink($this->setFixturePath(slash(path: '/fixtures/application-read/upload/multi_file.txt')));
    }
}
