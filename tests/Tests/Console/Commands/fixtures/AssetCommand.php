<?php

declare(strict_types=1);

namespace App\Commands;

use Omega\Console\AbstractCommand;
use Omega\Console\Traits\PrinterTrait;
use function Omega\Console\style;

class AssetCommand extends AbstractCommand
{
    use PrinterTrait;

    /**
     * Register command.
     *
     * @var array<int, array<string, mixed>>
     */
    public static array $command = [
        [
            "cmd"       => "Asset",
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
        style("Asset")->out(false);

        return 0;
    }
}
