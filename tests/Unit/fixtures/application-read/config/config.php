<?php

return [
    // app config
    'BASEURL'               => '/',
 //   'timezone'   => env('APP_TIMEZONE', 'UTC'),
    'APP_KEY'               => '',
    'environment'           => 'prod',
    'name'                  => 'Omega',
    'version'               => '2.0.0',
    'debug'                 => false,

    'COMMAND_PATH'          => DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Commands' . DIRECTORY_SEPARATOR,
    'CONTROLLER_PATH'       => DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR,
    'MODEL_PATH'            => DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Models' . DIRECTORY_SEPARATOR,
    'MIDDLEWARE'            => DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Middlewares' . DIRECTORY_SEPARATOR,
    'SERVICE_PROVIDER'      => DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Providers' . DIRECTORY_SEPARATOR,
    'CONFIG'                => DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR,
    'SERVICES_PATH'         => DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'services' . DIRECTORY_SEPARATOR,
    'VIEW_PATH'             => DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR,
    'COMPONENT_PATH'        => DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR, // phpcs:ignore
    'STORAGE_PATH'          => DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR,
    'CACHE_PATH'            => DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR, // phpcs:ignore
    'CACHE_VIEW_PATH'       => DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR, // phpcs:ignore
    'PUBLIC_PATH'           => DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR,
    'MIGRATION_PATH'        => DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR, // phpcs:ignore
    'SEEDER_PATH'           => DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'seeders' . DIRECTORY_SEPARATOR,

    'providers'             => [
        // provider class name
    ],

    // db config
    'DB_HOST'               => 'localhost',
    'DB_USER'               => 'root',
    'DB_PASS'               => '',
    'DB_NAME'               => '',

    // pusher
    'PUSHER_APP_ID'         => '',
    'PUSHER_APP_KEY'        => '',
    'PUSHER_APP_SECRET'     => '',
    'PUSHER_APP_CLUSTER'    => '',

    // redis driver
    'REDIS_HOST'            => '127.0.0.1',
    'REDIS_PASS'            => '',
    'REDIS_PORT'            => 6379,

    // memcahed
    'MEMCACHED_HOST'        => '127.0.0.1',
    'MEMCACHED_PASS'        => '',
    'MEMCACHED_PORT'        => 6379,

    // view config
    'VIEW_PATHS' => [
        DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR,
    ],
    'VIEW_EXTENSIONS' => [
        '.template.php',
        '.php',
    ],
    'COMPILED_VIEW_PATH' => DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR, // phpcs:ignore

    // cache config
    'cache' => [
        'default' => 'file',
        'storage' => [
            'file'   => [
                'ttl'  => 3600,
                'path' => DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR,
            ],
            'memory' => [
                'ttl' => 3600,
            ],
        ],
    ],

    // db config
    'db' => [
        'default'     => 'sqlite',
        'connections' => [
            'sqlite' => [
                'driver'   => 'sqlite',
                'database' => DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'database.sqlite',
            ],
        ],
    ],

    // logging config
    'logging' => [
        'default' => 'stream',
        'stream'  => [
            'type'    => 'stream',
            'path'    => DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs'
                . DIRECTORY_SEPARATOR . 'omega.log',
            'minimum' => 'debug',
            'options' => [],
        ],
    ],
];
