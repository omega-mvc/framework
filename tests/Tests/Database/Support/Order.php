<?php

declare(strict_types=1);

namespace Tests\Database\Support;

use Omega\Database\Model\Model;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Model::class)]
class Order extends Model
{
    protected string $tableName = 'orders';
}
