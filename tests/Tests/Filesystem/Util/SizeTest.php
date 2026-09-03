<?php

declare(strict_types=1);

namespace Tests\Filesystem\Util;

use InvalidArgumentException;
use Omega\Filesystem\Util\Size;

covers(Size::class);

it('computes size from content', function (): void {
    expect(Size::fromContent('hello'))->toBe(5);
});

it('computes size from empty content', function (): void {
    expect(Size::fromContent(''))->toBe(0);
});

it('computes size from a file', function (): void {
    $tmpFile = tempnam(sys_get_temp_dir(), 'omega_');
    file_put_contents($tmpFile, 'test');

    try {
        expect(Size::fromFile($tmpFile))->toBe(4);
    } finally {
        unlink($tmpFile);
    }
});

it('throws InvalidArgumentException for non-existent file', function (): void {
    Size::fromFile('/nonexistent/path/file.txt');
})->throws(InvalidArgumentException::class);

it('computes size from a resource', function (): void {
    $handle = fopen('php://memory', 'r+');
    fwrite($handle, 'test content');
    rewind($handle);

    $size = Size::fromResource($handle);
    expect($size)->toBeInt();
    fclose($handle);
});
