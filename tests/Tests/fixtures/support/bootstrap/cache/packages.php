<?php

use Tests\Support\Bootstrap\Support\TestVendorServiceProvider;

return [
    'omega-mvc/firstpackage' => [
        'providers' => [
            TestVendorServiceProvider::class,
        ],
    ],
];
