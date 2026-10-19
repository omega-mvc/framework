<?php

declare(strict_types=1);

use Omega\Router\Router;

Router::get('/web', static fn (): string => 'web-route');