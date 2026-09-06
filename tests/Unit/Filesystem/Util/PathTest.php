<?php

declare(strict_types=1);

namespace Tests\Filesystem\Util;

use Omega\Filesystem\Util\Path;

covers(Path::class);

it('normalizes a path with double dots', function (): void {
    expect(Path::normalize('/home/user/docs/../../etc'))->toBe('/home/etc');
});

it('normalizes a path with single dots', function (): void {
    expect(Path::normalize('/home/./user/test'))->toBe('/home/user/test');
});

it('normalizes a path with double slashes', function (): void {
    expect(Path::normalize('/home//user//test'))->toBe('/home/user/test');
});

it('normalizes a path with backslashes', function (): void {
    expect(Path::normalize('C:\\Users\\test'))->toBe('c:/Users/test');
});

it('normalizes a relative path with dots', function (): void {
    expect(Path::normalize('relative/../other'))->toBe('other');
});

it('normalizes complex path with mixed segments', function (): void {
    expect(Path::normalize('/a/b/./c/../d'))->toBe('/a/b/d');
});

it('handles root path with dots', function (): void {
    expect(Path::normalize('/..'))->toBe('/');
});

it('detects absolute unix paths', function (): void {
    expect(Path::isAbsolute('/home/user'))->toBeTrue();
});

it('detects relative paths', function (): void {
    expect(Path::isAbsolute('relative/path'))->toBeFalse();
});

it('detects windows drive letter paths', function (): void {
    expect(Path::isAbsolute('C:\\Users'))->toBeTrue();
});

it('detects UNC paths', function (): void {
    expect(Path::isAbsolute('//server/share'))->toBeTrue();
});

it('returns empty prefix for relative paths', function (): void {
    expect(Path::getAbsolutePrefix('relative/path'))->toBe('');
});

it('returns slash prefix for unix paths', function (): void {
    expect(Path::getAbsolutePrefix('/home/user'))->toBe('/');
});

it('returns lowercase drive prefix for windows paths', function (): void {
    expect(Path::getAbsolutePrefix('C:\\Users'))->toBe('c:');
});

it('returns double slash prefix for UNC paths', function (): void {
    expect(Path::getAbsolutePrefix('//server/share'))->toBe('//');
});

it('returns normalized dirname', function (): void {
    expect(Path::dirname('/a/b/c.txt'))->toBe('/a/b');
});

it('normalizes backslashes in dirname', function (): void {
    expect(Path::dirname('C:\\a\\b\\c.txt'))->toBe('C:/a/b');
});
