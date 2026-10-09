<?php

declare(strict_types=1);

namespace Tests\Http\Upload;

use Omega\Http\Exceptions\FolderNotExistsException;
use Omega\Http\Upload\UploadFile;
use Omega\Http\Upload\UploadMultiFile;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

use function file_exists;
use function filesize;
use function filetype;
use function ini_get;
use function trim;
use function unlink;

#[CoversClass(FolderNotExistsException::class)]
#[CoversClass(UploadFile::class)]
#[CoversClass(UploadMultiFile::class)]
final class UploadFileTest extends TestCase
{
    /**
     * @var array{
     *     file_1: array{name: string, type: string, tmp_name: string, error: int, size: int},
     *     file_2: array{
     *         name: array<int, string>,
     *         type: array<int, string>,
     *         tmp_name: array<int, string>,
     *         error: array<int, int>,
     *         size: array<int, int>
     *     }
     * }
     */
    private array $files;

    private UploadFile $upload;

    protected function setUp(): void
    {
        parent::setUp();

        if (!ini_get('file_uploads')) {
            $this->markTestSkipped('file_uploads is disabled in php.ini');
        }

        $this->files = [
            'file_1' => [
                'name'     => 'test123.txt',
                'type'     => 'file',
                'tmp_name' => __DIR__ . '/../fixtures/application-read/upload/test123.tmp',
                'error'    => 0,
                'size'     => 1,
            ],
            'file_2' => [
                'name'     => ['test123.txt', 'test234.txt'],
                'type'     => ['file', 'file'],
                'tmp_name' => [
                    __DIR__ . '/../fixtures/application-read/upload/test123.tmp',
                    __DIR__ . '/../fixtures/application-read/upload/test234.tmp',
                ],
                'error'    => [0, 0],
                'size'     => [1, 1],
            ],
        ];

        $size = filesize($this->files['file_1']['tmp_name']);
        $type = filetype($this->files['file_1']['tmp_name']);

        $this->files['file_1']['size'] = $size === false ? 0 : $size;
        $this->files['file_1']['type'] = $type === false ? 'file' : $type;

        $this->upload = new UploadFile($this->files['file_1']);
        $this->upload
            ->markTest(true)
            ->setFileName('success')
            ->setFileTypes(['txt', 'md'])
            ->setFolderLocation(__DIR__ . '/../fixtures/application-read/upload/')
            ->setMaxFileSize(91)
            ->setMimeTypes(['file']);
    }

    protected function tearDown(): void
    {
        $file = __DIR__ . '/../fixtures/application-read/upload/success.txt';
        if (file_exists($file)) {
            unlink($file);
        }

        parent::tearDown();
    }

    public function testCanUploadFileValid(): void
    {
        $this->upload->upload();

        $this->assertTrue($this->upload->success());
        $this->assertEquals('success', $this->upload->getError());
        $this->assertEquals(
            'This is a story about something that happened long ago when your grandfather was a child.',
            trim($this->upload->get())
        );
    }

    public function testCanUploadFileInvalidFileType(): void
    {
        $this->upload->setFileTypes(['md'])->upload();

        $this->assertFalse($this->upload->success());
    }

    public function testCanUploadFileInvalidFileFolder(): void
    {
        $this->expectException(FolderNotExistsException::class);

        $this->upload->setFolderLocation('/unknown');
    }

    public function testCanUploadFileInvalidFileSize(): void
    {
        $this->upload->setMaxFileSize(89)->upload();

        $this->assertFalse($this->upload->success());
    }

    public function testCanUploadFileInvalidMime(): void
    {
        $this->upload->setMimeTypes(['image/jpeg'])->upload();

        $this->assertFalse($this->upload->success());
    }

    public function testCanUploadFileInvalidNoFileUpload(): void
    {
        $this->files['file_1']['error'] = 4;

        $upload = new UploadFile($this->files['file_1']);
        $upload
            ->markTest(true)
            ->setFileName('success')
            ->setFileTypes(['txt', 'md'])
            ->setFolderLocation(__DIR__ . '/../fixtures/application-read/upload/')
            ->setMaxFileSize(91)
            ->setMimeTypes(['file']);

        $this->assertFalse($upload->success());

        // reset
        $this->files['file_1']['error'] = 0;
    }

    public function testCanMultiUploadFileButSingleFile(): void
    {
        $upload = new UploadMultiFile($this->files['file_2']);
        $upload
            ->markTest(true)
            ->setFileName('multi_file_')
            ->setFileTypes(['txt', 'md'])
            ->setFolderLocation(__DIR__ . '/../fixtures/application-read/upload/')
            ->setMaxFileSize(91)
            ->setMimeTypes(['file'])
            ->uploads();

        $this->assertTrue($upload->success());
        $this->assertFileIsReadable(__DIR__ . '/../fixtures/application-read/upload/multi_file_0.txt');
        $this->assertFileIsReadable(__DIR__ . '/../fixtures/application-read/upload/multi_file_1.txt');

        unlink(__DIR__ . '/../fixtures/application-read/upload/multi_file_0.txt');
        unlink(__DIR__ . '/../fixtures/application-read/upload/multi_file_1.txt');
    }
}
