<?php

declare(strict_types=1);

namespace Tests\Validator\Messages;

use Omega\Validator\Messages\Message;
use Omega\Validator\Messages\MessagePool;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(MessagePool::class)]
final class MessagepoolTest extends TestCase
{
    public function testCanAddMessageUsingMethodMagicGet(): void
    {
        $v                 = new MessagePool();
        $v->test->required = 'test';

        $m           = new Message();
        $m->required = 'test';

        $this->assertEquals($m, $v->Messages()['test']);
    }

    public function testCanAddMessageUsingMethodField(): void
    {
        $v                         = new MessagePool();
        $v->field('test')->required = 'test';

        $m           = new Message();
        $m->required = 'test';

        $this->assertEquals($m, $v->Messages()['test']);
    }

    public function testCanAddMessageUsingMethodMagicSetWithMessageInstance(): void
    {
        $v           = new MessagePool();
        $m           = new Message();
        $m->required = 'test';
        $v->test     = $m;

        $this->assertEquals($m, $v->Messages()['test']);
    }
}
