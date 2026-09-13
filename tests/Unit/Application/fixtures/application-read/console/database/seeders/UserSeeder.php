<?php

declare(strict_types=1);

namespace Database\Seeders;

use Omega\Database\Seeder\AbstractSeeder;

use function password_hash;

use const PASSWORD_DEFAULT;

class UserSeeder extends AbstractSeeder
{
    public function run(): void
    {
        $this->create('users')
            ->values([
                'user'     => 'test',
                'password' => password_hash('password', PASSWORD_DEFAULT),
                'stat'     => 10,
            ])
            ->execute();
    }
}
