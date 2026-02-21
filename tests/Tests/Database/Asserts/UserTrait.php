<?php

declare(strict_types=1);

namespace Tests\Database\Asserts;

use Omega\Database\Query\Query;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertTrue;

trait UserTrait
{
    protected function assertUserExist(string $user): void
    {
        $data  = Query::from('users', $this->pdo)
            ->select(['user'])
            ->equal('user', $user)
            ->all();

        assertTrue(count($data) === 1, 'expect user exist in database');
    }

    protected function assertUserNotExist(string $user): void
    {
        $data  = Query::from('users', $this->pdo)
            ->select(['user'])
            ->equal('user', $user)
            ->all();

        assertTrue(count($data) === 0, 'expect user exist in database');
    }

    protected function assertUserStat(string $user, int $expect): void
    {
        $data  = Query::from('users', $this->pdo)
            ->select(['stat'])
            ->equal('user', $user)
            ->all();

        assertEquals($expect, (int) $data[0]['stat'], 'expect user stat');
    }
}
