<?php

use Omega\Router\Router;

Router::get('/test', fn () => 'test')->name('test')->middleware(['test']);
Router::get('/test/(:id)', fn () => 'empty');
Router::prefix('test/')->group(function () {
    Router::post('/test/post', fn () => 'post')->name('post');
});
