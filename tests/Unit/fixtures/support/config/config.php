<?php

return [
    'debug'       => true,
    'providers'   => [],
    'db'          => [
        'default'     => 'sqlite',
        'connections' => [
            'sqlite' => [
                'driver'   => 'sqlite',
                'database' => DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'database.sqlite',
            ],
        ],
    ],
    'logging'     => [
        'default' => 'stream',
        'stream'  => [
            'type'    => 'stream',
            'path'    => DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs'
                . DIRECTORY_SEPARATOR . 'omega.log',
            'minimum' => 'debug',
            'options' => [],
        ],
    ],
    'cache'       => [
        'default' => 'memory',
        'storage' => [
            'memory' => [
                'ttl' => 3600,
            ],
        ],
    ],
];
