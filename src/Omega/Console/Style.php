<?php

declare(strict_types=1);

namespace Omega\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class Style
{
    private SymfonyStyle $io;

    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    public function info(string $message): void
    {
        $this->io->info($message);
    }

    public function error(string $message): void
    {
        $this->io->error($message);
    }

    public function warning(string $message): void
    {
        $this->io->warning($message);
    }

    public function comment(string $message): void
    {
        $this->io->comment($message);
    }

    public function success(string $message): void
    {
        $this->io->success($message);
    }

    public function choice(string $question, array $choices, $default = null): mixed
    {
        return $this->io->choice($question, $choices, $default);
    }

    public function ask(string $question, $default = null): mixed
    {
        return $this->io->ask($question, $default);
    }

    public function confirm(string $question, bool $default = true): bool
    {
        return $this->io->confirm($question, $default);
    }

    public function title(string $message): void
    {
        $this->io->title($message);
    }

    public function section(string $message): void
    {
        $this->io->section($message);
    }

    public function text(string|array $message): void
    {
        $this->io->text($message);
    }

    public function writeln(string|array $messages): void
    {
        $this->io->writeln($messages);
    }

    public function newLine(int $count = 1): void
    {
        $this->io->newLine($count);
    }

    public function note(string|array $message): void
    {
        $this->io->note($message);
    }

    public function write(string|array $messages, bool $newline = false): void
    {
        $this->io->write($messages, $newline);
    }

    public function table(array $headers, array $rows): void
    {
        $this->io->table($headers, $rows);
    }
}
