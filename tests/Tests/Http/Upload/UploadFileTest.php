<?php

declare(strict_types=1);

namespace Tests\Http\Upload;

use Omega\Http\Exceptions\FileNotExistsException;
use Omega\Http\Exceptions\FileNotUploadedException;
use Omega\Http\Exceptions\FolderNotExistsException;
use Omega\Http\Exceptions\MultiFileUploadDetectException;
use Omega\Http\Upload\AbstractUpload;
use Omega\Http\Upload\UploadFile;
use Omega\Http\Upload\UploadMultiFile;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

use function array_map;
use function file_exists;
use function filesize;
use function filetype;
use function ini_get;
use function restore_error_handler;
use function rmdir;
use function set_error_handler;
use function trim;
use function unlink;

#[CoversClass(FileNotExistsException::class)]
#[CoversClass(FileNotUploadedException::class)]
#[CoversClass(FolderNotExistsException::class)]
#[CoversClass(MultiFileUploadDetectException::class)]
#[CoversClass(AbstractUpload::class)]
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

    public function testThrowsWhenMultipleFilesArePassedToSingleUpload(): void
    {
        $this->expectException(MultiFileUploadDetectException::class);
        $this->expectExceptionMessage('Single files detected use `UploadMultiFile` instances of `UploadFile`');

        new UploadFile($this->files['file_2']);
    }

    public function testThrowsWhenGettingAnUploadThatWasNotSuccessful(): void
    {
        $files = $this->files['file_1'];
        $files['error'] = 4;

        $upload = new UploadFile($files);
        $upload
            ->markTest(true)
            ->setFileName('not_uploaded')
            ->setFileTypes(['txt', 'md'])
            ->setFolderLocation(__DIR__ . '/../fixtures/application-read/upload/')
            ->setMaxFileSize(91)
            ->setMimeTypes(['file']);

        $this->expectException(FileNotUploadedException::class);
        $this->expectExceptionMessage('File not uploaded `%s`');

        $upload->get();
    }

    public function testThrowsWhenTheUploadedFileIsMissingOnGet(): void
    {
        $this->upload->upload();

        $destination = __DIR__ . '/../fixtures/application-read/upload/success.txt';
        unlink($destination);

        set_error_handler(static fn (): bool => true);
        try {
            $this->expectException(FileNotExistsException::class);
            $this->expectExceptionMessage('File location not exists `' . $destination . '`');

            $this->upload->get();
        } finally {
            restore_error_handler();
        }
    }

    public function testExposesTheConfiguredFileTypes(): void
    {
        $this->assertSame(['txt', 'md'], $this->upload->getFileTypes());
    }

    public function testRejectsTheStreamWhenNoFileWasUploaded(): void
    {
        $files          = $this->files['file_1'];
        $files['error'] = 4;

        $upload = new UploadFile($files);
        $upload
            ->markTest(true)
            ->setFileName('no_file')
            ->setFileTypes(['txt', 'md'])
            ->setFolderLocation(__DIR__ . '/../fixtures/application-read/upload/')
            ->setMaxFileSize(91)
            ->setMimeTypes(['file']);

        $this->assertSame('', $upload->upload());
        $this->assertFalse($upload->success());
        $this->assertSame('no file upload', $upload->getError());
    }

    public function testStreamsUsingMoveUploadedFileWhenTestModeIsDisabled(): void
    {
        $upload = new UploadFile($this->files['file_1']);
        $upload
            ->setFileName('moved')
            ->setFileTypes(['txt', 'md'])
            ->setFolderLocation(__DIR__ . '/../fixtures/application-read/upload/')
            ->setMaxFileSize(91)
            ->setMimeTypes(['file']);

        $destination = __DIR__ . '/../fixtures/application-read/upload/moved.txt';

        $this->assertSame($destination, $upload->upload());
        $this->assertFalse($upload->success());
    }

    public function testCreatesAFolderOnlyWhenItDoesNotExist(): void
    {
        $folder = __DIR__ . '/../fixtures/application-read/upload/created_folder';

        try {
            $this->assertTrue($this->upload->creatFolder($folder));
            $this->assertDirectoryExists($folder);
            $this->assertFalse($this->upload->creatFolder($folder));
        } finally {
            if (file_exists($folder)) {
                rmdir($folder);
            }
        }
    }

    public function testStreamsMultipleFilesInTestMode(): void
    {
        $folder = __DIR__ . '/../fixtures/application-read/upload/';
        $upload = $this->configuredMultiUpload($this->files['file_2'], true, 'multi_stream');

        try {
            $this->assertSame([
                $folder . 'multi_stream0.txt',
                $folder . 'multi_stream1.txt',
            ], $upload->uploads());
            $this->assertTrue($upload->success());
        } finally {
            $this->removeGeneratedFiles($folder, 'multi_stream');
        }
    }

    public function testStreamsMultipleFilesWithoutTestMode(): void
    {
        $folder = __DIR__ . '/../fixtures/application-read/upload/';
        $upload = $this->configuredMultiUpload($this->files['file_2'], false, 'multi_moved');

        $this->assertSame([
            $folder . 'multi_moved0.txt',
            $folder . 'multi_moved1.txt',
        ], $upload->uploads());
        $this->assertFalse($upload->success());
    }

    public function testStreamsOneMultiFileInTestMode(): void
    {
        $folder = __DIR__ . '/../fixtures/application-read/upload/';
        $files  = $this->files['file_2'];
        $files['name']     = ['a.txt'];
        $files['type']     = ['file'];
        $files['tmp_name'] = [$folder . 'test123.tmp'];
        $files['error']    = [0];
        $files['size']     = [1];

        $upload = $this->configuredMultiUpload($files, true, 'multi_single_test');

        try {
            $this->assertSame([$folder . 'multi_single_test0.txt'], $upload->uploads());
            $this->assertTrue($upload->success());
        } finally {
            $this->removeGeneratedFiles($folder, 'multi_single_test');
        }
    }

    public function testStreamsOneMultiFileWithoutTestMode(): void
    {
        $folder = __DIR__ . '/../fixtures/application-read/upload/';
        $files  = $this->files['file_2'];
        $files['name']     = ['a.txt'];
        $files['type']     = ['file'];
        $files['tmp_name'] = [$folder . 'test123.tmp'];
        $files['error']    = [0];
        $files['size']     = [1];

        $upload = $this->configuredMultiUpload($files, false, 'multi_single_moved');

        $this->assertSame([$folder . 'multi_single_moved0.txt'], $upload->uploads());
        $this->assertFalse($upload->success());
    }

    public function testStreamsNoFilesWithoutTestMode(): void
    {
        $files = [
            'name'     => 'a.txt',
            'type'     => [],
            'tmp_name' => [],
            'error'    => [],
            'size'     => [],
        ];

        $upload = $this->configuredMultiUpload($files, false, 'multi_empty_moved');

        $this->assertSame([], $upload->uploads());
        $this->assertFalse($upload->success());
    }

    public function testNormalizesSingleFileStructureForMultiUpload(): void
    {
        $folder = __DIR__ . '/../fixtures/application-read/upload/';
        $upload = $this->configuredMultiUpload($this->files['file_1'], true, 'multi_shape');

        try {
            $this->assertSame([$folder . 'multi_shape0.txt'], $upload->uploads());
            $this->assertTrue($upload->success());
            $this->assertSame([
                'This is a story about something that happened long ago when your grandfather was a child.',
            ], array_map('trim', $upload->getAll()));
        } finally {
            $this->removeGeneratedFiles($folder, 'multi_shape');
        }
    }

    public function testSkipsMalformedEntriesWhenNormalizingMultiFiles(): void
    {
        $folder = __DIR__ . '/../fixtures/application-read/upload/';
        $files  = $this->files['file_2'];

        $files['name']     = ['a.txt', 42, 'b.md'];
        $files['type']     = ['file', [], 'file'];
        $files['tmp_name'] = [$folder . 'test123.tmp', null, $folder . 'test234.tmp'];
        $files['error']    = [0, 'bad', 0];
        $files['size']     = [1, 'bad', 1];

        $upload = $this->configuredMultiUpload($files, true, 'multi_filtered');

        try {
            $this->assertSame([
                $folder . 'multi_filtered0.txt',
                $folder . 'multi_filtered1.md',
            ], $upload->uploads());
            $this->assertTrue($upload->success());
            $this->assertCount(2, $upload->getAll());
        } finally {
            $this->removeGeneratedFiles($folder, 'multi_filtered');
        }
    }

    public function testMultiGetAllThrowsWhenNoUploadSucceeded(): void
    {
        $files          = $this->files['file_2'];
        $files['error'] = [4, 4];

        $upload = $this->configuredMultiUpload($files, true, 'multi_not_uploaded');

        $this->expectException(FileNotUploadedException::class);
        $this->expectExceptionMessage('File not uploaded `%s`');

        $upload->getAll();
    }

    public function testMultiGetAllReturnsUploadedContents(): void
    {
        $folder = __DIR__ . '/../fixtures/application-read/upload/';
        $upload = $this->configuredMultiUpload($this->files['file_2'], true, 'multi_get_all');

        try {
            $upload->uploads();

            $this->assertSame([
                'This is a story about something that happened long ago when your grandfather was a child.',
                'This is a story about something that happened long ago when your grandfather was a child.',
            ], array_map('trim', $upload->getAll()));
        } finally {
            $this->removeGeneratedFiles($folder, 'multi_get_all');
        }
    }

    public function testMultiGetAllThrowsWhenAnUploadedFileIsMissing(): void
    {
        $folder      = __DIR__ . '/../fixtures/application-read/upload/';
        $upload      = $this->configuredMultiUpload($this->files['file_2'], true, 'multi_missing_get');
        $destination = $folder . 'multi_missing_get0.txt';

        try {
            $upload->uploads();
            unlink($destination);

            set_error_handler(static fn (): bool => true);
            try {
                $this->expectException(FileNotExistsException::class);
                $this->expectExceptionMessage('File location not exists `' . $destination . '`');

                $upload->getAll();
            } finally {
                restore_error_handler();
            }
        } finally {
            $this->removeGeneratedFiles($folder, 'multi_missing_get');
        }
    }

    public function testMultiRejectsAnInvalidFolder(): void
    {
        $this->expectException(FolderNotExistsException::class);
        $this->expectExceptionMessage('Folder location not exists `/unknown`');

        (new UploadMultiFile($this->files['file_2']))->setFolderLocation('/unknown');
    }

    public function testDeletesAnExistingFileAndReturnsFalseForMissingFile(): void
    {
        $path = __DIR__ . '/../fixtures/application-read/upload/delete_me.txt';

        try {
            file_put_contents($path, 'tmp');

            $this->assertTrue($this->upload->delete($path));
            $this->assertFileDoesNotExist($path);
            $this->assertFalse($this->upload->delete($path));
        } finally {
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    public function testReturnsTheDestinationWhenTheCopyFails(): void
    {
        $files                = $this->files['file_1'];
        $files['tmp_name']    = '/unknown/missing.tmp';

        $upload = (new UploadFile($files))
            ->markTest(true)
            ->setFileName('missing_source')
            ->setFileTypes(['txt', 'md'])
            ->setFolderLocation(__DIR__ . '/../fixtures/application-read/upload/')
            ->setMaxFileSize(91)
            ->setMimeTypes(['file']);

        set_error_handler(static fn (): bool => true);
        try {
            $this->assertSame(
                __DIR__ . '/../fixtures/application-read/upload/missing_source.txt',
                $upload->upload()
            );
            $this->assertFalse($upload->success());
        } finally {
            restore_error_handler();
        }
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function validationScenarios(): array
    {
        $folder = __DIR__ . '/../fixtures/application-read/upload/';
        $tmp1   = $folder . 'test123.tmp';
        $tmp2   = $folder . 'test234.tmp';
        $tmp3   = $folder . 'multi_file0.txt';

        $make = static function (array $names, array $errors, array $types, array $sizes, array $tmp): array {
            return [
                'name'     => $names,
                'type'     => $types,
                'tmp_name' => $tmp,
                'error'    => $errors,
                'size'     => $sizes,
            ];
        };

        return [
            'error on second of two' => [
                $make(['a.txt', 'b.txt'], [0, 4], ['file', 'file'], [1, 1], [$tmp1, $tmp2]),
                'no file upload',
            ],
            'error on first of three' => [
                $make(
                    ['a.txt', 'b.md', 'c.txt'],
                    [4, 0, 0],
                    ['file', 'file', 'file'],
                    [1, 1, 1],
                    [$tmp1, $tmp2, $tmp3]
                ),
                'no file upload',
            ],
            'error on second of three' => [
                $make(
                    ['a.txt', 'b.md', 'c.txt'],
                    [0, 4, 0],
                    ['file', 'file', 'file'],
                    [1, 1, 1],
                    [$tmp1, $tmp2, $tmp3]
                ),
                'no file upload',
            ],
            'error on third of three' => [
                $make(
                    ['a.txt', 'b.md', 'c.txt'],
                    [0, 0, 4],
                    ['file', 'file', 'file'],
                    [1, 1, 1],
                    [$tmp1, $tmp2, $tmp3]
                ),
                'no file upload',
            ],
            'extension on first of three' => [
                $make(
                    ['a.pdf', 'b.md', 'c.txt'],
                    [0, 0, 0],
                    ['file', 'file', 'file'],
                    [1, 1, 1],
                    [$tmp1, $tmp2, $tmp3]
                ),
                'file type not support',
            ],
            'extension on second of two' => [
                $make(['a.txt', 'b.pdf'], [0, 0], ['file', 'file'], [1, 1], [$tmp1, $tmp2]),
                'file type not support',
            ],
            'extension on third of three' => [
                $make(
                    ['a.txt', 'b.md', 'c.pdf'],
                    [0, 0, 0],
                    ['file', 'file', 'file'],
                    [1, 1, 1],
                    [$tmp1, $tmp2, $tmp3]
                ),
                'file type not support',
            ],
            'mime on first of three' => [
                $make(
                    ['a.txt', 'b.md', 'c.txt'],
                    [0, 0, 0],
                    ['image/jpeg', 'file', 'file'],
                    [1, 1, 1],
                    [$tmp1, $tmp2, $tmp3]
                ),
                'file type not support',
            ],
            'mime on second of two' => [
                $make(['a.txt', 'b.md'], [0, 0], ['file', 'image/jpeg'], [1, 1], [$tmp1, $tmp2]),
                'file type not support',
            ],
            'mime on third of three' => [
                $make(
                    ['a.txt', 'b.md', 'c.txt'],
                    [0, 0, 0],
                    ['file', 'file', 'image/jpeg'],
                    [1, 1, 1],
                    [$tmp1, $tmp2, $tmp3]
                ),
                'file type not support',
            ],
            'size on first of three' => [
                $make(
                    ['a.txt', 'b.md', 'c.txt'],
                    [0, 0, 0],
                    ['file', 'file', 'file'],
                    [100, 1, 1],
                    [$tmp1, $tmp2, $tmp3]
                ),
                'file size too large',
            ],
            'size on second of two' => [
                $make(['a.txt', 'b.md'], [0, 0], ['file', 'file'], [1, 100], [$tmp1, $tmp2]),
                'file size too large',
            ],
            'size on third of three' => [
                $make(
                    ['a.txt', 'b.md', 'c.txt'],
                    [0, 0, 0],
                    ['file', 'file', 'file'],
                    [1, 1, 100],
                    [$tmp1, $tmp2, $tmp3]
                ),
                'file size too large',
            ],
            'all valid one file' => [
                $make(['a.txt'], [0], ['file'], [1], [$tmp1]),
                'success',
            ],
            'all valid two files' => [
                $make(['a.txt', 'b.md'], [0, 0], ['file', 'file'], [1, 1], [$tmp1, $tmp2]),
                'success',
            ],
            'all valid three files' => [
                $make(
                    ['a.txt', 'b.md', 'c.txt'],
                    [0, 0, 0],
                    ['file', 'file', 'file'],
                    [1, 1, 1],
                    [$tmp1, $tmp2, $tmp3]
                ),
                'success',
            ],
            'malformed files fallback' => [
                [
                    'name'     => 'a.txt',
                    'type'     => [],
                    'tmp_name' => [],
                    'error'    => [],
                    'size'     => [],
                ],
                'success',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $files
     */
    #[DataProvider('validationScenarios')]
    public function testValidatesMultiFileScenarios(array $files, string $expected): void
    {
        $folder = __DIR__ . '/../fixtures/application-read/upload/';
        $upload = $this->configuredMultiUpload($files);

        try {
            $upload->uploads();

            $this->assertSame($expected, $upload->getError());
        } finally {
            $this->removeGeneratedFiles($folder, 'multi_validation');
        }
    }

    /**
     * @param array<string, mixed> $files
     */
    private function configuredMultiUpload(
        array $files,
        bool $testMode = true,
        string $name = 'multi_validation'
    ): UploadMultiFile {
        /** @var array<string, string|int|array<int, string>|array<int, int>> $normalized */
        $normalized = (static fn (): mixed => $files)();

        $upload = new UploadMultiFile($normalized);
        if ($testMode) {
            $upload->markTest(true);
        }

        return $upload
            ->setFileName($name)
            ->setFileTypes(['txt', 'md'])
            ->setFolderLocation(__DIR__ . '/../fixtures/application-read/upload/')
            ->setMaxFileSize(91)
            ->setMimeTypes(['file']);
    }

    private function removeGeneratedFiles(string $folder, string $name): void
    {
        for ($key = 0; $key < 3; $key++) {
            foreach (['txt', 'md'] as $extension) {
                $path = $folder . $name . $key . '.' . $extension;
                if (file_exists($path)) {
                    unlink($path);
                }
            }
        }
    }
}
