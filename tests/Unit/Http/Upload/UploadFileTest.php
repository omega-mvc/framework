<?php

declare(strict_types=1);

namespace Tests\Http\Upload;

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

uses(FixturesPathTrait::class);

covers(FolderNotExistsException::class);
covers(UploadFile::class);
covers(UploadMultiFile::class);

beforeEach(function (): void {
    if (!ini_get('file_uploads')) {
        $this->markTestSkipped('file_uploads is disabled in php.ini');
    }

    $this->files = [
        'file_1' => [
            'name'     => 'test123.txt',
            'type'     => 'file',
            'tmp_name' => $this->setFixturePath(
                '/fixtures/application-read/upload/test123.tmp'
            ),
            'error'    => 0,
            'size'     => 1,
        ],
        'file_2' => [
            'name'     => ['test123.txt', 'test234.txt'],
            'type'     => ['file', 'file'],
            'tmp_name' => [
                $this->setFixturePath('/fixtures/application-read/upload/test123.tmp'),
                $this->setFixturePath('/fixtures/application-read/upload/test234.tmp'),
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
        ->setFolderLocation($this->setFixturePath('/fixtures/application-read/upload/'))
        ->setMaxFileSize(91)
        ->setMimeTypes(['file']);
});

afterEach(function (): void {
    $file = $this->setFixturePath('/fixtures/application-read/upload/success.txt');
    if (file_exists($file)) {
        unlink($file);
    }
});

it('can upload file valid', function (): void {
    $this->upload->upload();

    expect($this->upload->success())->toBeTrue();
    expect($this->upload->getError())->toEqual('success');
    expect(trim($this->upload->get()))->toEqual(
        'This is a story about something that happened long ago when your grandfather was a child.'
    );
});

it('can upload file invalid file type', function (): void {
    $this->upload->setFileTypes(['md'])->upload();

    expect($this->upload->success())->toBeFalse();
});

it('can upload file invalid file folder', function (): void {
    $this->expectException(FolderNotExistsException::class);

    $this->upload->setFolderLocation('/unknown');
});

it('can upload file invalid file size', function (): void {
    $this->upload->setMaxFileSize(89)->upload();

    expect($this->upload->success())->toBeFalse();
});

it('can upload file invalid mime', function (): void {
    $this->upload->setMimeTypes(['image/jpeg'])->upload();

    expect($this->upload->success())->toBeFalse();
});

it('can upload file invalid no file upload', function (): void {
    $this->files['file_1']['error'] = 4;

    $upload = new UploadFile($this->files['file_1']);
    $upload
        ->markTest(true)
        ->setFileName('success')
        ->setFileTypes(['txt', 'md'])
        ->setFolderLocation($this->setFixturePath('/fixtures/application-read/upload/'))
        ->setMaxFileSize(91)
        ->setMimeTypes(['file']);

    expect($upload->success())->toBeFalse();

    // reset
    $this->files['file_1']['error'] = 0;
});

it('can multi upload file but single file', function (): void {
    $upload = new UploadMultiFile($this->files['file_2']);
    $upload
        ->markTest(true)
        ->setFileName('multi_file_')
        ->setFileTypes(['txt', 'md'])
        ->setFolderLocation($this->setFixturePath('/fixtures/application-read/upload/'))
        ->setMaxFileSize(91)
        ->setMimeTypes(['file'])
        ->uploads();

    expect($upload->success())->toBeTrue();
    expect($this->setFixturePath('/fixtures/application-read/upload/multi_file_0.txt'))->toBeReadableFile();
    expect($this->setFixturePath('/fixtures/application-read/upload/multi_file_1.txt'))->toBeReadableFile();

    unlink($this->setFixturePath('/fixtures/application-read/upload/multi_file_0.txt'));
    unlink($this->setFixturePath('/fixtures/application-read/upload/multi_file_1.txt'));
});