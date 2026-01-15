<?php

declare(strict_types=1);

/**
 * @var array<string, string|array{
 *      accessor?: string,
 *      excludes?: array<string, bool>,
 *      replaces?: array<string, string>,
 *      with?: array<string, array{param?: string[], return?: string}>,
 * }>
 */
return [
    'Cache'    => 'Omega\\Cache\\CacheFactory',
    'Config'   => 'Omega\\Config\\ConfigRepository',
    'DB'       => [
        'accessor' => 'Omega\\Database\\DatabaseManager',
        'with'     => [
            'resultset' => [
                'return' => 'mixed[]|false',
            ],
            'getLogs' => [
                'return' => 'array<int, array<string, float|string|null>>',
            ],
        ],
    ],
    'Hash'     => 'Omega\\Security\\Hashing\\HashManager',
    'PDO'      => 'Omega\\Database\\Connection',
    'Schedule' => [
        'accessor' => 'Omega\\Cron\\Schedule',
        'replaces' => [
            'ScheduleTime'   => '\\Omega\\Cron\\ScheduleTime',
            'ScheduleTime[]' => '\\Omega\\Cron\\ScheduleTime[]',
        ],
    ],
    'Schema'   => 'Omega\\Database\\Schema',
    'View'     => 'Omega\\View\\Templator',
    'Vite'     => 'Omega\\Support\\Vite',
];
