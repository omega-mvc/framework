<?php

declare(strict_types=1);

namespace App\Commands;

use Omega\Console\AbstractCommand;
use Omega\Console\Traits\PrinterTrait;
use function Omega\Console\style;

class CacheCommand extends AbstractCommand
{
    use PrinterTrait;

    /**
     * Register command.
     *
     * @var array<int, array<string, mixed>>
     */
    public static array $command = [
        [
            "cmd"       => "Cache",
            "mode"      => "full",
            "class"     => self::class,
            "fn"        => "main",
        ],
    ];

    /**
     * @return array<string, array<string, string|string[]>>
     */
    public function printHelp(): array
      {
          return [
              'commands' => [],
              'options'  => [],
              'relation' => [],
          ];
      }

    public function main(): int
    {
        style("Cache")->out(false);

        return 0;
    }
}
