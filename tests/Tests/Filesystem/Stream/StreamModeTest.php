<?php

declare(strict_types=1);

namespace Tests\Filesystem\Util;

use Omega\Filesystem\Stream\StreamMode;

covers(StreamMode::class);

it('parses read mode', function (): void {
    $mode = new StreamMode('r');
    expect($mode->getMode())->toBe('r');
    expect($mode->allowsRead())->toBeTrue();
    expect($mode->allowsWrite())->toBeFalse();
    expect($mode->allowsExistingFileOpening())->toBeTrue();
    expect($mode->allowsNewFileOpening())->toBeFalse();
    expect($mode->impliesExistingContentDeletion())->toBeFalse();
    expect($mode->impliesPositioningCursorAtTheBeginning())->toBeTrue();
    expect($mode->impliesPositioningCursorAtTheEnd())->toBeFalse();
    expect($mode->isBinary())->toBeFalse();
    expect($mode->isText())->toBeTrue();
});

it('parses write mode', function (): void {
    $mode = new StreamMode('w');
    expect($mode->allowsRead())->toBeFalse();
    expect($mode->allowsWrite())->toBeTrue();
    expect($mode->allowsExistingFileOpening())->toBeTrue();
    expect($mode->allowsNewFileOpening())->toBeTrue();
    expect($mode->impliesExistingContentDeletion())->toBeTrue();
    expect($mode->impliesPositioningCursorAtTheBeginning())->toBeTrue();
    expect($mode->impliesPositioningCursorAtTheEnd())->toBeFalse();
});

it('parses append mode', function (): void {
    $mode = new StreamMode('a');
    expect($mode->allowsRead())->toBeFalse();
    expect($mode->allowsWrite())->toBeTrue();
    expect($mode->allowsExistingFileOpening())->toBeTrue();
    expect($mode->allowsNewFileOpening())->toBeTrue();
    expect($mode->impliesExistingContentDeletion())->toBeFalse();
    expect($mode->impliesPositioningCursorAtTheBeginning())->toBeFalse();
    expect($mode->impliesPositioningCursorAtTheEnd())->toBeTrue();
});

it('parses exclusive create mode', function (): void {
    $mode = new StreamMode('x');
    expect($mode->allowsRead())->toBeFalse();
    expect($mode->allowsWrite())->toBeTrue();
    expect($mode->allowsExistingFileOpening())->toBeFalse();
    expect($mode->allowsNewFileOpening())->toBeTrue();
    expect($mode->impliesExistingContentDeletion())->toBeFalse();
    expect($mode->impliesPositioningCursorAtTheBeginning())->toBeTrue();
    expect($mode->impliesPositioningCursorAtTheEnd())->toBeFalse();
});

it('parses read-plus mode', function (): void {
    $mode = new StreamMode('r+');
    expect($mode->allowsRead())->toBeTrue();
    expect($mode->allowsWrite())->toBeTrue();
    expect($mode->allowsExistingFileOpening())->toBeTrue();
    expect($mode->allowsNewFileOpening())->toBeFalse();
    expect($mode->impliesExistingContentDeletion())->toBeFalse();
});

it('parses write-plus mode', function (): void {
    $mode = new StreamMode('w+');
    expect($mode->allowsRead())->toBeTrue();
    expect($mode->allowsWrite())->toBeTrue();
    expect($mode->allowsNewFileOpening())->toBeTrue();
    expect($mode->impliesExistingContentDeletion())->toBeTrue();
});

it('parses append-plus mode', function (): void {
    $mode = new StreamMode('a+');
    expect($mode->allowsRead())->toBeTrue();
    expect($mode->allowsWrite())->toBeTrue();
    expect($mode->impliesPositioningCursorAtTheEnd())->toBeTrue();
});

it('parses binary read mode', function (): void {
    $mode = new StreamMode('rb');
    expect($mode->getMode())->toBe('rb');
    expect($mode->allowsRead())->toBeTrue();
    expect($mode->isBinary())->toBeTrue();
    expect($mode->isText())->toBeFalse();
});

it('parses binary write mode', function (): void {
    $mode = new StreamMode('wb');
    expect($mode->allowsWrite())->toBeTrue();
    expect($mode->isBinary())->toBeTrue();
});
