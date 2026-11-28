<?php

declare(strict_types=1);

if (!function_exists('litespeed_finish_request')) {
    function litespeed_finish_request(): void
    {
        $GLOBALS['litespeed_finish_request_called'] = true;
    }
}

if (!function_exists('litespeed_finish_request_called')) {
    function litespeed_finish_request_called(): bool
    {
        return ($GLOBALS['litespeed_finish_request_called'] ?? false) === true;
    }
}
