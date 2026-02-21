<?php

declare(strict_types=1);

namespace Tests\Database\Support;

use Omega\Database\Model\Model;

class Order extends Model
{
    protected string $tableName = 'orders';
}
