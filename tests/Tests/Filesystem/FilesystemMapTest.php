<?php

declare(strict_types=1);

namespace Tests\Filesystem;

use Omega\Filesystem\FilesystemMap;
use Omega\Filesystem\Filesystem;
use Omega\Filesystem\Adapter\Memory\InMemory;
use InvalidArgumentException;

covers(FilesystemMap::class);

beforeEach(function (): void {
    $this->map = new FilesystemMap();
    $this->fs = new Filesystem(new InMemory());
});

it('sets and gets a filesystem', function (): void {
    $this->map->set('local', $this->fs);
    expect($this->map->get('local'))->toBe($this->fs);
});

it('checks if a filesystem exists', function (): void {
    expect($this->map->has('local'))->toBeFalse();
    $this->map->set('local', $this->fs);
    expect($this->map->has('local'))->toBeTrue();
});

it('returns all registered filesystems', function (): void {
    $this->map->set('fs1', $this->fs);
    $all = $this->map->all();
    expect($all)->toHaveCount(1);
});

it('removes a filesystem', function (): void {
    $this->map->set('local', $this->fs);
    $this->map->remove('local');
    expect($this->map->has('local'))->toBeFalse();
});

it('clears all filesystems', function (): void {
    $this->map->set('fs1', $this->fs);
    $this->map->clear();
    expect($this->map->all())->toBe([]);
});

it('throws on invalid name', function (): void {
    $this->map->set('invalid name!', $this->fs);
})->throws(InvalidArgumentException::class);

it('throws when getting non-existent filesystem', function (): void {
    $this->map->get('missing');
})->throws(InvalidArgumentException::class);

it('throws when removing non-existent filesystem', function (): void {
    $this->map->remove('missing');
})->throws(InvalidArgumentException::class);

it('accepts hyphens underscores and numbers in name', function (): void {
    $this->map->set('my-fs_1', $this->fs);
    expect($this->map->has('my-fs_1'))->toBeTrue();
});
