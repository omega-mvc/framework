<?php

declare(strict_types=1);

namespace Tests\Console;

use Omega\Console\Style;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

covers(Style::class);

function streamWith(string $content)
{
    $stream = fopen('php://memory', 'r+');
    fwrite($stream, $content);
    rewind($stream);

    return $stream;
}

it('writes an indented single line', function (): void {
    $output = new BufferedOutput();
    $style = new Style(new ArrayInput([]), $output);

    $style->writeln('alpha');

    expect($output->fetch())->toBe('  alpha' . PHP_EOL);
});

it('writes multiple lines keeping empty lines unindented', function (): void {
    $output = new BufferedOutput();
    $style = new Style(new ArrayInput([]), $output);

    $style->writeln(['alpha', '', 'beta']);

    expect($output->fetch())->toBe('  alpha' . PHP_EOL . PHP_EOL . '  beta' . PHP_EOL);
});

it('trims trailing line endings before indenting', function (): void {
    $output = new BufferedOutput();
    $style = new Style(new ArrayInput([]), $output);

    $style->writeln("alpha\r\n");

    expect($output->fetch())->toBe('  alpha' . PHP_EOL);
});

it('renders all styled message blocks', function (): void {
    $output = new BufferedOutput();
    $style = new Style(new ArrayInput([]), $output);

    $style->success('done');
    $style->error('bad');
    $style->warning('careful');
    $style->comment('see note');
    $style->note('remember');
    $style->info('known');

    $display = $output->fetch();

    expect($display)->toContain('SUCCESS  done')
        ->and($display)->toContain('ERROR  bad')
        ->and($display)->toContain('WARNING  careful')
        ->and($display)->toContain('COMMENT see note')
        ->and($display)->toContain('NOTE  remember')
        ->and($display)->toContain('INFO  known');
});

it('accepts iterable messages in styled blocks', function (): void {
    $output = new BufferedOutput();
    $style = new Style(new ArrayInput([]), $output);

    $style->success(['first', 'second']);

    $display = $output->fetch();

    expect($display)->toContain('first')
        ->and($display)->toContain('second');
});

it('inserts a blank line between blocks that end with content', function (): void {
    $output = new BufferedOutput();
    $style = new Style(new ArrayInput([]), $output);

    $style->writeln('plain');
    $style->success('after');

    expect($output->fetch())->toContain('  plain' . PHP_EOL . PHP_EOL . '   SUCCESS');
});

it('renders titles, sections and text', function (): void {
    $output = new BufferedOutput();
    $style = new Style(new ArrayInput([]), $output);

    $style->title('Title');
    $style->section('Body');
    $style->text('hidden note');
    $style->text(['visible line']);

    $display = $output->fetch();

    expect($display)->toContain('Title')
        ->and($display)->toContain('== Body ==')
        ->and($display)->toContain('  hidden note')
        ->and($display)->toContain('  visible line');
});

it('returns the answer provided by the user', function (): void {
    $input = new ArrayInput([]);
    $input->setStream(streamWith("Ada\n"));
    $output = new BufferedOutput();
    $style = new Style($input, $output);

    expect($style->ask('Your name?', 'default'))->toBe('Ada');
});

it('confirms when the user answers yes', function (): void {
    $input = new ArrayInput([]);
    $input->setStream(streamWith("yes\n"));
    $output = new BufferedOutput();
    $style = new Style($input, $output);

    expect($style->confirm('Continue?'))->toBeTrue();
});

it('falls back to the default when no answer is provided', function (): void {
    $input = new ArrayInput([]);
    $input->setStream(streamWith(''));
    $output = new BufferedOutput();
    $style = new Style($input, $output);

    expect($style->confirm('Continue?', false))->toBeFalse();
});

it('writes the requested number of blank lines', function (): void {
    $output = new BufferedOutput();
    $style = new Style(new ArrayInput([]), $output);

    $style->newLine(2);

    expect($output->fetch())->toBe(PHP_EOL . PHP_EOL);
});

it('creates a progress bar with a message', function (): void {
    $output = new BufferedOutput();
    $style = new Style(new ArrayInput([]), $output);

    $bar = $style->progressBar(10, 'Working');

    expect($bar)->toBeInstanceOf(ProgressBar::class)
        ->and($bar->getMaxSteps())->toBe(10)
        ->and($bar->getMessage())->toBe('Working');

    $output->fetch();
});

it('creates a progress bar without a message', function (): void {
    $output = new BufferedOutput();
    $style = new Style(new ArrayInput([]), $output);

    $bar = $style->progressBar(0);

    expect($bar)->toBeInstanceOf(ProgressBar::class)
        ->and($bar->getMaxSteps())->toBe(0)
        ->and($bar->getMessage())->toBeNull();

    $output->fetch();
});

it('renders a family badge with a message', function (): void {
    $output = new BufferedOutput();
    $output->setDecorated(true);
    $style = new Style(new ArrayInput([]), $output);

    $style->db('Connected to the database.');

    expect($output->fetch())->toBe(
        '  ' . "\e[30;46m DB \e[39;49m" . ' Connected to the database.' . PHP_EOL . PHP_EOL
    );
});

it('writes a pair of values separated by a dot filler', function (): void {
    putenv('COLUMNS=80');

    try {
        $output = new BufferedOutput();
        $style = new Style(new ArrayInput([]), $output);

        $style->spread('users 1 MB', '2026-09-09');

        expect($output->fetch())->toBe(
            '  users 1 MB ' . str_repeat('.', 56) . ' 2026-09-09' . PHP_EOL
        );
    } finally {
        putenv('COLUMNS');
    }
});

it('keeps a minimum dot filler when the content overflows', function (): void {
    putenv('COLUMNS=10');

    try {
        $output = new BufferedOutput();
        $style = new Style(new ArrayInput([]), $output);

        $style->spread('x', 'y', 20);

        expect($output->fetch())->toBe('  x .. y' . PHP_EOL);
    } finally {
        putenv('COLUMNS');
    }
});