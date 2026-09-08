<?php

use Omega\Validator\Messages\Message;
use Omega\Validator\Messages\MessagePool;

it('can add message using method __get', function () {
    $v                  = new MessagePool();
    $v->test->required  = 'test';

    $m              = new Message();
    $m->required    = 'test';
    expect($v->Messages()['test'])->toEqual($m);
});

it('can add message using method field', function () {
    $v                          = new MessagePool();
    $v->field('test')->required = 'test';

    $m              = new Message();
    $m->required    = 'test';
    expect($v->Messages()['test'])->toEqual($m);
});
