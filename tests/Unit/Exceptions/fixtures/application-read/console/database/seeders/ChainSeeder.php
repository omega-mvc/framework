<?php

declare(strict_types=1);

namespace Database\Seeders;

use Omega\Database\Seeder\AbstractSeeder;

class ChainSeeder extends AbstractSeeder
{
    public function run(): void
    {
        $this->call(BasicSeeder::class);
    }
}
