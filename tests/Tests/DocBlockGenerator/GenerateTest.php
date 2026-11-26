<?php

declare(strict_types=1);

namespace Tests\DocBlockGenerator;

use Omega\DocBlockGenerator\Constant;
use Omega\DocBlockGenerator\ConstPool;
use Omega\DocBlockGenerator\Generate;
use Omega\DocBlockGenerator\Method;
use Omega\DocBlockGenerator\MethodPool;
use Omega\DocBlockGenerator\Property;
use Omega\DocBlockGenerator\PropertyPool;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

#[CoversClass(Generate::class)]
final class GenerateTest extends TestCase
{
    public function testInvokeSetsNameAndReturnsSameInstance(): void
    {
        $generate = new Generate();

        $this->assertSame($generate, $generate('Invoked'));
        $this->assertStringContainsString('class Invoked', $generate->generate());
    }

    public function testStaticFactoryCreatesGeneratorWithName(): void
    {
        $generate = Generate::static('StaticName');

        $this->assertInstanceOf(Generate::class, $generate);
        $this->assertStringContainsString('class StaticName', $generate->generate());
    }

    public function testToStringGeneratesCode(): void
    {
        $this->assertSame((new Generate('Foo'))->generate(), (string) new Generate('Foo'));
    }

    public function testSaveWritesGeneratedFileAndReturnsByteCount(): void
    {
        $directory = sys_get_temp_dir();
        $generate  = new Generate('SavedClass');

        $written = $generate->save($directory);
        $path    = $directory . '/SavedClass.php';

        try {
            $this->assertIsInt($written);
            $this->assertGreaterThan(0, $written);
            $this->assertFileExists($path);
            $this->assertStringContainsString('class SavedClass', (string) file_get_contents($path));
        } finally {
            @unlink($path);
        }
    }

    /**
     * @return iterable<string, array{int, string}>
     */
    public static function provideRules(): iterable
    {
        yield 'abstract' => [Generate::SET_ABSTRACT, 'abstract class'];
        yield 'trait' => [Generate::SET_TRAIT, 'trait class'];
        yield 'unknown falls back to empty rule' => [99, 'class'];
    }

    #[DataProvider('provideRules')]
    public function testRuleTextCoversEveryKeyword(int $rule, string $expected): void
    {
        $generate = (new Generate('Foo'))->rule($rule);

        $this->assertStringContainsString($expected, $generate->generate());
    }

    public function testRuleAndSetFinalComposeDeclaration(): void
    {
        $generate = (new Generate('Foo'))->rule(Generate::SET_ABSTRACT)->setFinal();

        $this->assertStringContainsString('final abstract class Foo', $generate->generate());

        $generate->setFinal(false)->rule(Generate::SET_TRAIT);

        $this->assertStringContainsString('trait class Foo', $generate->generate());
        $this->assertStringNotContainsString('final', $generate->generate());
    }

    public function testFluentSettersForClassNameAndHead(): void
    {
        $generate = (new Generate())
            ->name('Renamed')
            ->namespace('App\\Domain')
            ->uses(['App\\Support\\Helper'])
            ->use('App\\Contracts\\Contract')
            ->extend('BaseClass')
            ->implements(['SecondInterface'])
            ->implement('FirstInterface')
            ->traits(['SecondTrait'])
            ->trait('FirstTrait')
            ->body('public int $x = 1;');

        $code = $generate->generate();

        $this->assertStringContainsString('namespace App\\Domain;', $code);
        $this->assertStringContainsString('use App\\Contracts\\Contract;', $code);
        $this->assertStringContainsString('use App\\Support\\Helper;', $code);
        $this->assertStringContainsString('class Renamed extends BaseClass', $code);
        $this->assertStringContainsString('implements SecondInterface, FirstInterface', $code);
        $this->assertStringContainsString('use SecondTrait, FirstTrait;', $code);
        $this->assertStringContainsString('public int $x = 1;', $code);
    }

    public function testDeclareHelpersAddDirectives(): void
    {
        $code = (new Generate('Foo'))
            ->addDeclare('ticks', 2)
            ->setDeclareStrictTypes()
            ->generate();

        $this->assertStringContainsString('declare(ticks=2);', $code);
        $this->assertStringContainsString('declare(strict_types=1);', $code);
    }

    public function testSetEndWithNewLineControlsTrailingNewline(): void
    {
        $generate = new Generate('Foo');

        $this->assertStringEndsNotWith("\n", $generate->generate());
        $this->assertStringEndsWith("\n", $generate->setEndWithNewLine()->generate());
        $this->assertStringEndsNotWith("\n", $generate->setEndWithNewLine(false)->generate());
    }

    public function testConstantsAcceptSingleCallableAndPool(): void
    {
        $generate = new Generate('Foo');

        $this->assertSame($generate, $generate->constants(Constant::new('ONE')));

        $generate->constants(static function (ConstPool $pool): void {
            $pool->name('TWO')->equal('2');
        });

        $pool = new ConstPool();
        $pool->name('THREE')->equal('3');
        $generate->constants($pool);

        $code = $generate->generate();

        $this->assertStringContainsString('const ONE', $code);
        $this->assertStringContainsString('const TWO = 2', $code);
        $this->assertStringContainsString('const THREE = 3', $code);
    }

    public function testPropertiesAcceptSingleCallableAndPool(): void
    {
        $generate = new Generate('Foo');

        $this->assertSame($generate, $generate->properties(Property::new('one')));

        $generate->properties(static function (PropertyPool $pool): void {
            $pool->name('two')->dataType('string');
        });

        $pool = new PropertyPool();
        $pool->name('three')->dataType('int');
        $generate->properties($pool);

        $code = $generate->generate();

        $this->assertStringContainsString('$one;', $code);
        $this->assertStringContainsString('string $two;', $code);
        $this->assertStringContainsString('int $three;', $code);
    }

    public function testMethodsAcceptSingleCallableAndPool(): void
    {
        $generate = new Generate('Foo');

        $this->assertSame($generate, $generate->methods(Method::new('one')));

        $generate->methods(static function (MethodPool $pool): void {
            $pool->name('two')->setReturnType('int');
        });

        $pool = new MethodPool();
        $pool->name('three');
        $generate->methods($pool);

        $code = $generate->generate();

        $this->assertStringContainsString('function one()', $code);
        $this->assertStringContainsString('function two(): int', $code);
        $this->assertStringContainsString('function three()', $code);
    }

    public function testAddHelpersReturnCreatedDefinitions(): void
    {
        $generate = new Generate('Foo');

        $this->assertInstanceOf(Constant::class, $generate->addConst('ADDED_CONST'));
        $this->assertInstanceOf(Property::class, $generate->addProperty('addedProperty'));
        $this->assertInstanceOf(Method::class, $generate->addMethod('addedMethod'));

        $code = $generate->generate();

        $this->assertStringContainsString('const ADDED_CONST', $code);
        $this->assertStringContainsString('$addedProperty;', $code);
        $this->assertStringContainsString('function addedMethod()', $code);
    }

    public function testPreReplaceAndReplaceAcceptStrings(): void
    {
        $code = (new Generate('Foo'))
            ->preReplace('class', 'final class')
            ->replace('Foo', 'Bar')
            ->generate();

        $this->assertStringContainsString('final class Bar', $code);
    }

    public function testPreReplaceAndReplaceAcceptArrays(): void
    {
        $code = (new Generate('Foo'))
            ->preReplace(['class', '{{head}}'], ['abstract class', 'Renamed'])
            ->replace(['Renamed', 'abstract'], ['Generated', 'final'])
            ->generate();

        $this->assertStringContainsString('final class Generated', $code);
    }

    public function testCustomTemplateOverridesDefault(): void
    {
        $generate = (new Generate('Foo'))->customizeTemplate('CUSTOM:{{head}}');

        $this->assertSame('CUSTOM:Foo', $generate->generate());
    }

    public function testCommentIsRenderedInBeforeScope(): void
    {
        $code = (new Generate('Foo'))->addComment('a comment')->generate();

        $this->assertStringContainsString('/** a comment */', $code);
    }

    public function testGenerateWithoutElementsUsesDefaultTemplate(): void
    {
        $this->assertSame("<?php\n\nclass NewClass\n{\n\n}", (new Generate())->generate());
    }
}
