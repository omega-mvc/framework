<?php

declare(strict_types=1);

namespace Tests\Database\Support;

use Omega\Database\Model\Model;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Model::class)]
class Profile extends Model
{
    protected string $tableName  = 'profiles';
    protected string $primaryKey = 'user';

    public function filterGender(string $gender): static
    {
        $this->where->equal('gender', $gender);

        return $this;
    }

    public function filterAge(int $greade_that): static
    {
        $this->where->compare('age', '>', $greade_that);

        return $this;
    }
}
