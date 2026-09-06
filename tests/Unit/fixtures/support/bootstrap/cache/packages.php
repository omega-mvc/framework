<?php

use Tests\Application\Fixtures\TestVendorServiceProvider;

return [
    'omega-mvc/firstpackage' => [
        'providers' => [
            TestVendorServiceProvider::class,
        ],
    ],
];
