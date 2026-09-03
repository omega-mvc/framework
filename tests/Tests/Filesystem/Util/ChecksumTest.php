<?php

declare(strict_types=1);

namespace Tests\Filesystem\Util;

use Omega\Filesystem\Util\Checksum;
use RuntimeException;

covers(Checksum::class);

it('computes md5 checksum from content', function (): void {
    expect(Checksum::fromContent('hello'))->toBe(md5('hello'));
});

it('computes md5 checksum from empty content', function (): void {
    expect(Checksum::fromContent(''))->toBe(md5(''));
});

it('computes md5 checksum from a file', function (): void {
    $tmpFile = tempnam(sys_get_temp_dir(), 'omega_');
    file_put_contents($tmpFile, 'test content');

    try {
        expect(Checksum::fromFile($tmpFile))->toBe(md5_file($tmpFile));
    } finally {
        unlink($tmpFile);
    }
});

it('throws RuntimeException for non-existent file', function (): void {
    Checksum::fromFile('/nonexistent/path/file.txt');
})->throws(RuntimeException::class);
