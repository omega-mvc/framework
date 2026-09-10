<?php

declare(strict_types=1);

namespace Tests\Event\Support;

use Omega\Event\EventInterface;

class PrivateMethodService
{
    protected function handle(EventInterface $event): void
    {
    }
}