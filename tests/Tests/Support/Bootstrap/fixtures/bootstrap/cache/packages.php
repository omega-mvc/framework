<?php

use Tests\Support\Bootstrap\TestVendorServiceProvider;

return [
    'omega-mvc/nexus' => [
        'providers' => [
            TestVendorServiceProvider::class,
        ],
    ],
];
