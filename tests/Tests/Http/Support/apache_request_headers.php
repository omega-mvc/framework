<?php

declare(strict_types=1);

if (!function_exists('apache_request_headers')) {
    /**
     * Stub used only by RequestFactoryCoverageTest in an isolated process.
     *
     * @return array<mixed, mixed>
     */
    function apache_request_headers(): array
    {
        return [
            'X-Apache' => 'yes',
            3          => 'skip',
            'X-Bad'    => ['skip'],
        ];
    }
}
