<?php

namespace Omega\Console;

use Override;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Style extends SymfonyStyle
{
    protected string $indent = '  ';
    protected string $separator = ' ';
    protected bool $isLastLineEmpty = true;

    private OutputInterface $output;

    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        parent::__construct($input, $output);
    }

    #[Override]
    public function writeln(string|iterable $messages, int $type = self::OUTPUT_NORMAL): void
    {
        foreach ((array) $messages as $message) {
            $message = (string) $message;

            $message = rtrim($message, "\r\n");

            if ($message !== '') {
                $message = $this->indent . $message;
                $this->isLastLineEmpty = false;
            } else {
                $this->isLastLineEmpty = true;
            }

            parent::writeln($message, $type);
        }
    }

    #[Override]
    public function success(string|iterable $message): void
    {
        $message = $this->processMessage($message);

        $this->ensureTopSpacing();

        $this->writeln('<bg=green;fg=white> SUCCESS </>' . $this->separator . $message);

        $this->newLine(); // 👈 riga vuota garantita
    }

    #[Override]
    public function error(string|iterable $message): void
    {
        $message = $this->processMessage($message);

        $this->ensureTopSpacing();

        $this->writeln('<bg=red;fg=white> ERROR </>' . $this->separator . $message);

        $this->newLine(); // 👈 riga vuota garantita
    }

    #[Override]
    public function warning(string|iterable $message): void
    {
        $message = $this->processMessage($message);

        $this->ensureTopSpacing();

        $this->writeln('<bg=yellow;fg=black> WARNING </>' . $this->separator . $message);

        $this->newLine(); // 👈 riga vuota garantita
    }

    #[Override]
    public function comment(string|iterable $message): void
    {
        $message = $this->processMessage($message);

        $this->ensureTopSpacing();

        $this->writeln('<fg=yellow>COMMENT</>' . $this->separator . $message);

        $this->newLine();
    }

    #[Override]
    public function note(string|iterable $message): void
    {
        $message = $this->processMessage($message);

        $this->ensureTopSpacing();

        $this->writeln('<bg=cyan;fg=black> NOTE </>' . $this->separator . $message);

        $this->newLine();
    }

    #[Override]
    public function info(string|iterable $message): void
    {
        $message = $this->processMessage($message);

        $this->ensureTopSpacing();

        $this->writeln('<bg=blue;fg=white> INFO </>' . $this->separator . $message);

        $this->newLine();
    }

    #[Override]
    public function title(string $message): void
    {
        $message = $this->processMessage($message);

        $this->ensureTopSpacing();

        $this->writeln("<options=bold;underscore>$message</>");

        $this->newLine();
    }

    #[Override]
    public function section(string $message): void
    {
        $message = $this->processMessage($message);

        $this->ensureTopSpacing();

        $this->writeln("<fg=cyan;options=bold>== $message ==</>");

        $this->newLine();
    }

    #[Override]
    public function text(string|array $message): void
    {
        $message = $this->processMessage($message);

        $this->ensureTopSpacing();

        $this->writeln($message);

        $this->newLine();
    }

    #[Override]
    public function ask(string $question, $default = null, $validator = null): mixed
    {
        $this->ensureTopSpacing();

        $question = $this->indent . "<fg=cyan;options=bold>?</> $question";

        $result = parent::ask($question, $default, $validator);

        $this->isLastLineEmpty = false;

        return $result;
    }
    #[Override]
    public function confirm(string $question, bool $default = true): bool
    {
        $this->ensureTopSpacing();

        $question = $this->indent . "<fg=cyan;options=bold>?</> $question";

        $result = parent::confirm($question, $default);

        $this->isLastLineEmpty = false;

        return $result;
    }

    private function processMessage(string|iterable $message): string
    {
        if (is_iterable($message)) {
            return implode(PHP_EOL, array_map(
                fn($m) => (string) $m,
                (array) $message
            ));
        }

        return (string) $message;
    }

    protected function ensureTopSpacing(): void
    {
        if (!$this->isLastLineEmpty) {
            parent::newLine();
            $this->isLastLineEmpty = true;
        }
    }

    #[Override]
    public function newLine(int $count = 1): void
    {
        parent::newLine($count);
        $this->isLastLineEmpty = true;
    }

    public function progressBar(int $max = 0, string $message = ''): ProgressBar
    {
        $this->ensureTopSpacing();

        $progressBar = new ProgressBar($this->output, $max);

        $progressBar->setFormat(
            $this->indent . " %current%/%max% [%bar%] %percent:3s%% \n" .
            $this->indent . " <fg=gray>%message%</>"
        );

        $progressBar->setBarCharacter('<fg=green>━</>');
        $progressBar->setEmptyBarCharacter('<fg=gray>─</>');
        $progressBar->setProgressCharacter('<fg=green>❯</>');

        if ($message) {
            $progressBar->setMessage($message);
        }

        $this->isLastLineEmpty = false;

        return $progressBar;
    }
}
