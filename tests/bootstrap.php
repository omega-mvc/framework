<?php

$_ENV['APP_ENV'] ??= 'testing';
$_ENV['OMEGA_TEST_MODE'] ??= 'normal';

putenv('APP_ENV=' . ($_ENV['APP_ENV'] ?? 'testing'));
putenv('OMEGA_TEST_MODE=' . ($_ENV['OMEGA_TEST_MODE'] ?? 'normal'));

require __DIR__ . '/../vendor/autoload.php';
