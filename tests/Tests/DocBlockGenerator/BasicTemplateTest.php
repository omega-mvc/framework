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
use Omega\DocBlockGenerator\Providers\NewConst;
use Omega\DocBlockGenerator\Providers\NewMethod;
use Omega\DocBlockGenerator\Providers\NewProperty;
use PhpParser\Builder\TraitUse;
use PhpParser\Builder\TraitUseAdaptation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase as PhpUnitTestCase;
use Tests\TestCase;

use function file_get_contents;
use function str_replace;

#[CoversClass(Constant::class)]
#[CoversClass(ConstPool::class)]
#[CoversClass(Generate::class)]
#[CoversClass(Method::class)]
#[CoversClass(MethodPool::class)]
#[CoversClass(Property::class)]
#[CoversClass(PropertyPool::class)]
#[CoversClass(NewConst::class)]
#[CoversClass(NewMethod::class)]
#[CoversClass(NewProperty::class)]
final class BasicTemplateTest extends TestCase
{
    private function basicTemplateGetExpected(string $expected): string
    {
        $fileName = __DIR__ . '/fixtures/template/' . $expected;
        $content  = file_get_contents($fileName);
        if (false === $content) {
            throw new \RuntimeException(sprintf('Fixture file "%s" is not readable.', $fileName));
        }

        return str_replace("\r\n", "\n", $content);
    }

    public function testGeneratesABasicClass(): void
    {
        $class = new Generate('NewClass');

        $class
            ->setDeclareStrictTypes()
            ->use(Generate::class)
            ->extend(PhpUnitTestCase::class)
            ->implement('testInterface')
            ->setEndWithNewLine();

        $this->assertEquals($this->basicTemplateGetExpected('basic_class'), $class);
    }

    public function testGeneratesAClassWithTraitPropertyAndMethod(): void
    {
        $class = new Generate('NewClass');

        $class
            ->use(Generate::class)
            ->extend(PhpUnitTestCase::class)
            ->implement('testInterface')
            ->traits([
                TraitUseAdaptation::class,
                TraitUse::class,
            ])
            ->constants(NewConst::name('TEST'))
            ->properties(NewProperty::name('test'))
            ->methods(NewMethod::name('test'))
            ->setEndWithNewLine();

        $this->assertEquals($this->basicTemplateGetExpected('class_with_trait_property_method'), $class->generate());
    }

    public function testGeneratesAClassWithTraitPropertyAndMethodFromTemplate(): void
    {
        $class = new Generate('NewClass');

        $class
            ->customizeTemplate("<?php\n{{before}}{{comment}}\n{{rule}}class\40{{head}} {\n\n{{body}}\n}\n?>{{end}}")
            ->tabIndent("\t")
            ->tabSize(2)

            ->use(Generate::class)
            ->extend(PhpUnitTestCase::class)
            ->implement('testInterface')
            ->traits([
                TraitUseAdaptation::class,
                TraitUse::class,
            ])
            ->constants(NewConst::name('TEST'))
            ->properties(NewProperty::name('test'))
            ->methods(
                NewMethod::name('test')
                    ->customizeTemplate('{{comment}}{{before}}function {{name}}({{params}}){{return type}} {{{new line}}{{body}}{{new line}}}') // phpcs:ignore
            )
            ->setEndWithNewLine();

        $this->assertEquals($this->basicTemplateGetExpected('class_with_custom_template'), $class->generate());
    }

    public function testGeneratesAClassWithComplexProperties(): void
    {
        $class = new Generate('NewClass');

        $class
            ->properties(
                NewProperty::name('test')
                    ->visibility(Property::PRIVATE_)
                    ->addComment('Test')
                    ->addLineComment()
                    ->addVariableComment('string')
                    ->expecting('= "works"')
            )
            ->properties(function (PropertyPool $property) {
                // multiple property
                for ($i = 0; $i < 10; $i++) {
                    $property->name('test_' . $i);
                }
            })
            ->setEndWithNewLine();

        // add property using addProperty
        $class
            ->addProperty('some_property')
            //->visibility(Property::PUBLIC_)
            ->visibility()
            ->dataType('array')
            ->expecting(
                [
                    '= array(',
                    '  \'one\'    => 1,',
                    '  \'two\'    => 2,',
                    '  \'bool\'   => false,',
                    '  \'string\' => \'string\'',
                    ')',
                ]
            )
            ->addVariableComment('array');

        // add property using PropertyPool
        $pool = new PropertyPool();
        for ($i = 1; $i < 6; $i++) {
            $pool
                ->name('from_pool_' . $i)
                //->visibility(Property::PUBLIC_)
                ->visibility()
                ->dataType('string')
                ->expecting('= \'pools_' . $i . '\'')
                ->addVariableComment('string')
            ;
        }
        $class->properties($pool);

        $this->assertEquals($this->basicTemplateGetExpected('class_with_complex_property'), $class->generate());
    }

    public function testGeneratesAClassWithComplexMethods(): void
    {
        $class = new Generate('NewClass');

        $class
            ->methods(
                NewMethod::name('test')
                    ->addComment('A method')
                    ->addLineComment()
                    ->addReturnComment('string', '$name', 'Test')
                    ->params(['string $name = "test"'])
                    ->setReturnType('string')
                    ->body(['return $name;'])
            )
            ->methods(function (MethodPool $method) {
                // multi function
                for ($i = 0; $i < 3; $i++) {
                    $method
                    ->name('test_' . $i)
                    ->params(['$param_' . $i])
                    ->setReturnType('int')
                    ->body(['return $param_' . $i . ';']);
                }
            })
            ->setEndWithNewLine();

        // add property using method
        $class
            ->addMethod('someTest')
            //->visibility(Method::PUBLIC_)
            ->visibility()
            ->setFinal()
            ->setStatic()
            ->params(['string $case', 'int $number'])
            ->setReturnType('bool')
            ->body([
                '$bool = true;',
                'return $bool;',
            ])
            ->addReturnComment('bool', 'true if true');

        // add property using PropertyPool
        $pool = new MethodPool();
        for ($i = 1; $i < 3; $i++) {
            $pool
                ->name('function_' . $i)
                ->visibility(Property::PUBLIC_)
                ->params(['string $param'])
                ->setReturnType('string')
                ->body(['return $param;'])
                ->addParamComment('string', '$param', 'String param')
                ->addReturnComment('string', 'Same as param')
            ;
        }
        $class->methods($pool);

        $this->assertEquals($this->basicTemplateGetExpected('class_with_complex_methods'), $class->generate());
    }

    public function testGeneratesAClassWithComplexConstants(): void
    {
        $class = new Generate('NewClass');

        $class
            ->constants(
                Constant::new('COMMENT')
                    ->addComment('a const with Comment')
            )
            ->constants(function (ConstPool $const) {
                for ($i = 0; $i < 10; $i++) {
                    $const
                        ->name('CONST_' . $i)
                        ->equal((string)$i);
                }
            })
            ->setEndWithNewLine();

        $class
            ->addConst('A_CONST')
            ->visibility(Constant::PRIVATE_)
            ->expecting('= true');
        $class
            ->addConst('B_CONST')
            ->visibility(Constant::PROTECTED_)
            ->expecting('= false');

        // add property using PropertyPool
        $pool = new ConstPool();
        for ($i = 1; $i < 4; $i++) {
            $pool
                ->name('CONSTPOOL_' . $i)
                ->expecting('= true')
            ;
        }
        $class->constants($pool);

        $this->assertEquals($this->basicTemplateGetExpected('class_with_complex_const'), $class->generate());
    }

    public function testGeneratesAClassWithComplexComments(): void
    {
        $class = new Generate('NewClass');

        $class
            ->addComment('A class with comment')
            ->addLineComment()
            ->addComment('@auth sonypradana@gmail.com')
            ->constants(
                Constant::new('COMMENT')
                    ->addComment('a const with Comment')
            )
            ->properties(
                Property::new('_property')
                    ->addVariableComment('string', 'String property')
            )
            ->methods(
                Method::new('someTest')
                    ->addComment('a function with comment')
                    ->addLineComment()
                    ->addVariableComment('string', 'sample')
                    ->addParamComment('string', '$test', 'Test')
                    ->addReturnComment('bool', 'true if true')
            )
            ->setEndWithNewLine();

        $this->assertEquals($this->basicTemplateGetExpected('class_with_complex_comment'), $class->generate());
    }

    public function testGeneratesAReplacedTemplate(): void
    {
        // pre replace
        $class = new Generate('old_class');

        $class->preReplace('class', 'trait');

        $this->assertEquals("<?php\n\ntrait old_class\n{\n\n}", $class->generate());

        // replace
        $class->replace(['old_class'], ['new_class']);

        $this->assertEquals("<?php\n\ntrait new_class\n{\n\n}", $class->generate());
    }
}
