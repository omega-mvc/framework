<?php

use Omega\Validator\Messages\Message;

it('can add message (__get)', function () {
    $message           = new Message();
    $message->required = 'test';

    expect($message->messages())->toMatchArray([
        'required' => 'test',
    ]);
});

it('can add message (array set)', function () {
    $message             = new Message();
    $message['required'] = 'test';

    expect($message->messages())->toMatchArray([
        'required' => 'test',
    ]);
});

it('can list message (Message())', function () {
    $message            = new Message();
    $message->required  = 'test';
    $message->alpha     = 'test';

    expect($message->messages())->toMatchArray([
        'required' => 'test',
        'alpha'    => 'test',
    ]);
});

it('can get message (array)', function () {
    $message                = new Message();
    $message['required']    = 'test';

    expect($message['required'])->toEqual('test');
});

it('can check message exists (array)', function () {
    $message             = new Message();
    $message['required'] = 'test';

    expect(isset($message['required']))->toBeTrue();
    expect(isset($message['alpha']))->toBeFalse();
});

it('can unset message (array)', function () {
    $message             = new Message();
    $message['required'] = 'test';

    unset($message['required']);

    expect(isset($message['required']))->toBeFalse();
    expect($message->messages())->toBe([]);
});

it('can add message using add method', function () {
    $message = new Message();

    $message->add(['required' => 'test']);

    expect($message->messages())->toMatchArray([
        'required' => 'test',
    ]);
});

it('can add empty messages using add method', function () {
    $message = new Message();

    $message->add([]);

    expect($message->messages())->toBe([]);
});
