<?php

declare(strict_types=1);

namespace Database\Seeders;

use Omega\Database\Seeder\AbstractSeeder;

use function Omega\Console\style;

class BasicSeeder extends AbstractSeeder
{
    public function run(): void
    {
        style('seed for basic seeder')->out(false);
    }
}
