<?php

declare(strict_types=1);

namespace Tests\Database\Support;

use Omega\Database\Model\Model;
use Omega\Database\Model\ModelCollection;
use Tests\Database\Model\Order;
use Tests\Database\Model\Profile;

class User extends Model
{
    protected string $tableName  = 'users';
    protected string $primaryKey = 'user';
    /** @var string[] Hide from shoing column */
    protected array $stash = ['password'];

    public function profile(): User|Profile
    {
        return $this->hasOne(Profile::class, 'user');
    }

    public function orders(): ModelCollection
    {
        return $this->hasMany(Order::class, 'user');
    }
}
