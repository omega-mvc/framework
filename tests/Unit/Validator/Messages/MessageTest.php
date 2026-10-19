<?php

declare(strict_types=1);

namespace Tests\Validator\Messages;

use Omega\Validator\Messages\Message;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(Message::class)]
final class MessageTest extends TestCase
{
    public function testCanAddMessageUsingMagicGet(): void
    {
        $message           = new Message();
        $message->required = 'test';

        $this->assertArrayHasKey('required', $message->messages());
        $this->assertEquals('test', $message->messages()['required']);
    }

    public function testCanAddMessageArraySet(): void
    {
        $message             = new Message();
        $message['required'] = 'test';

        $this->assertArrayHasKey('required', $message->messages());
        $this->assertEquals('test', $message->messages()['required']);
    }

    public function testCanListMessage(): void
    {
        $message           = new Message();
        $message->required = 'test';
        $message->alpha    = 'test';

        $messages = $message->messages();

        $this->assertArrayHasKey('required', $messages);
        $this->assertEquals('test', $messages['required']);
        $this->assertArrayHasKey('alpha', $messages);
        $this->assertEquals('test', $messages['alpha']);
    }

    public function testCanGetMessageArray(): void
    {
        $message             = new Message();
        $message['required'] = 'test';

        $this->assertEquals('test', $message['required']);
    }

    public function testCanCheckMessageExistsArray(): void
    {
        $message             = new Message();
        $message['required'] = 'test';

        $this->assertTrue(isset($message['required']));
        $this->assertFalse(isset($message['alpha']));
    }

    public function testCanUnsetMessageArray(): void
    {
        $message             = new Message();
        $message['required'] = 'test';

        unset($message['required']);

        $this->assertFalse(isset($message['required']));
        $this->assertSame([], $message->messages());
    }

    public function testCanAddMessageUsingAddMethod(): void
    {
        $message = new Message();

        $message->add(['required' => 'test']);

        $this->assertArrayHasKey('required', $message->messages());
        $this->assertEquals('test', $message->messages()['required']);
    }

    public function testCanAddEmptyMessagesUsingAddMethod(): void
    {
        $message = new Message();

        $message->add([]);

        $this->assertSame([], $message->messages());
    }
}
