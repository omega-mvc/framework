<?php

use Tests\Support\Bootstrap\Support\TestVendorServiceProvider;

return [
    'omega-mvc/nexus' => [
        'providers' => [
            TestVendorServiceProvider::class,
        ],
    ],
];
