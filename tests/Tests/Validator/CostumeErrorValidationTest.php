<?php

declare(strict_types=1);

namespace Tests\Validator;

use Omega\Validator\Messages\MessagePool;
use Omega\Validator\Rule;
use Omega\Validator\Rule\ValidPool;
use Omega\Validator\Validator;
use Tests\TestCase;

final class CostumeErrorValidationTest extends TestCase
{
    public function testCanChangeSupportedLanguage(): void
    {
        $val = new Validator(['test' => null]);

        // change lang before run validation
        $val
            ->lang('id')
            ->field('test')->required()
        ;

        $this->assertSame(['test' => 'Bagian Test harus diisi'], $val->getError());
    }

    public function testChangeSupportedLanguageAfterRunValidatorDoesntPerformAnything(): void
    {
        $val = new Validator(['test' => null]);

        $val->field('test')->required();
        $err = $val->getError();
        // change lang after run validation
        $val->lang('id');

        $this->assertSame(['test' => 'The Test field is required'], $err);
    }

    public function testCanCreateCostumeError(): void
    {
        Rule::set_error_message('required', '{field} can\'t be null');

        $val = new Validator(['tets' => null]);
        $val->test->required();

        $this->assertEquals('Test can\'t be null', $val->errors()->test);
    }

    public function testCanCreateCostumeErrorContaintNotMethod(): void
    {
        Rule::set_error_message('required', '{field} can\'t be null');

        $val = new Validator(['test' => 'null']);
        $val->test->not()->required();

        $this->assertEquals('Test can\'t be null', $val->errors()->test);
    }

    public function testCanCreateCostumeErrorMulty(): void
    {
        Rule::set_error_messages([
            'required' => '{field} can\'t be null',
            'min_len'  => '{field} less that 2',
        ]);

        $val = new Validator([
            'tets' => null, 'test2' => 'abc',
        ]);
        $val->test->required();
        $val->field('test2')->min_len(4);

        $errors = $val->getError();

        $this->assertEquals('Test can\'t be null', $errors['test']);
        $this->assertEquals('Test2 less that 2', $errors['test2']);
    }

    public function testCanCostumeFieldErrorMessage(): void
    {
        $v = Validator::make()->validation(fn (ValidPool $v): array => [
            $v('test')->required(),
        ]);
        $v->messages()->test->required = 'costume required message';

        $this->assertEquals(
            'costume required message',
            $v->errors->test
        );
    }

    public function testCanCostumeFieldErrorMessageOverideGlobalCostumeError(): void
    {
        Rule::set_error_message('required', '{field} can\'t be null');

        $v = Validator::make()->validation(fn (ValidPool $v): array => [
            $v('test')->required(),
        ]);

        $v->messages()->test->required = 'costume required message';

        $this->assertEquals(
            'costume required message',
            $v->errors->test
        );
    }

    public function testCanCostumeFieldErrorMessageUseMesssageArray(): void
    {
        $v = Validator::make()->validation(fn (ValidPool $v): array => [
            $v('test')->required(),
        ]);
        $v->messages()->test = [
            'required' => 'costume required message',
        ];

        $this->assertEquals(
            'costume required message',
            $v->errors->test
        );
    }

    public function testCanCostumeFieldErrorMessageUsingSetMessage(): void
    {
        $v = Validator::make()->validation(fn (ValidPool $v): array => [
            $v('test')->required(),
        ]);
        $v->setErrorMessages([
            'test' => [
                'required' => 'costume required message',
            ],
        ]);

        $this->assertEquals(
            'costume required message',
            $v->errors->test
        );
    }

    public function testCanCostumeErrorMessageUsingMessagePollWithDinamicPropertyInField(): void
    {
        $v = Validator::make()->validation(fn (ValidPool $v): array => [
            $v('test')->required(),
        ]);
        $v->messages()->field('test')->required = 'costume required message';

        $this->assertEquals(
            'costume required message',
            $v->errors->test
        );
    }

    public function testCanCostumeErrorMessageUsingMessagesWithCallback(): void
    {
        $v = Validator::make()->validation(fn (ValidPool $v): array => [
            $v('test')->required(),
            $v('test2')->required(),
        ]);
        $v->messages(static function (MessagePool $message): MessagePool {
            $message->field('test')->required = 'costume required message';

            return $message;
        })->field('test2')->required = 'costume required message';

        $this->assertEquals(
            'costume required message',
            $v->errors->test
        );
        $this->assertEquals(
            'costume required message',
            $v->errors->test2
        );
    }
}
