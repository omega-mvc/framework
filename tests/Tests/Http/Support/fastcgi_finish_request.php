<?php

declare(strict_types=1);

if (!function_exists('fastcgi_finish_request')) {
    function fastcgi_finish_request(): void
    {
        $GLOBALS['fastcgi_finish_request_called'] = true;
    }
}

if (!function_exists('fastcgi_finish_request_called')) {
    function fastcgi_finish_request_called(): bool
    {
        return ($GLOBALS['fastcgi_finish_request_called'] ?? false) === true;
    }
}
