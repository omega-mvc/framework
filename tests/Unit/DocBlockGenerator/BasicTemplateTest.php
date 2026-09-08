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
use PHPUnit\Framework\TestCase;

use function file_get_contents;
use function str_replace;

covers(Constant::class);
covers(ConstPool::class);
covers(Generate::class);
covers(Method::class);
covers(MethodPool::class);
covers(Property::class);
covers(PropertyPool::class);
covers(NewConst::class);
covers(NewMethod::class);
covers(NewProperty::class);

function basicTemplateGetExpected(string $expected): string
{
    $fileName = __DIR__ . '/../fixtures/template/' . $expected;
    $content  = file_get_contents($fileName);
    if (false === $content) {
        throw new \RuntimeException(sprintf('Fixture file "%s" is not readable.', $fileName));
    }

    return str_replace("\r\n", "\n", $content);
}

it('generates a basic class', function (): void {
    $class = new Generate('NewClass');

    $class
        ->setDeclareStrictTypes()
        ->use(Generate::class)
        ->extend(TestCase::class)
        ->implement('testInterface')
        ->setEndWithNewLine();

    expect($class)->toEqual(basicTemplateGetExpected('basic_class'));
});

it('generates a class with trait property and method', function (): void {
    $class = new Generate('NewClass');

    $class
        ->use(Generate::class)
        ->extend(TestCase::class)
        ->implement('testInterface')
        ->traits([
            TraitUseAdaptation::class,
            TraitUse::class,
        ])
        ->constants(NewConst::name('TEST'))
        ->properties(NewProperty::name('test'))
        ->methods(NewMethod::name('test'))
        ->setEndWithNewLine();

    expect($class->generate())->toEqual(basicTemplateGetExpected('class_with_trait_property_method'));
});

it('generates a class with trait property and method from template', function (): void {
    $class = new Generate('NewClass');

    $class
        ->customizeTemplate("<?php\n{{before}}{{comment}}\n{{rule}}class\40{{head}} {\n\n{{body}}\n}\n?>{{end}}")
        ->tabIndent("\t")
        ->tabSize(2)

        ->use(Generate::class)
        ->extend(TestCase::class)
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

    expect($class->generate())->toEqual(basicTemplateGetExpected('class_with_custom_template'));
});

it('generates a class with complex properties', function (): void {
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

    expect($class->generate())->toEqual(basicTemplateGetExpected('class_with_complex_property'));
});

it('generates a class with complex methods', function (): void {
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

    expect($class->generate())->toEqual(basicTemplateGetExpected('class_with_complex_methods'));
});

it('generates a class with complex constants', function (): void {
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

    expect($class->generate())->toEqual(basicTemplateGetExpected('class_with_complex_const'));
});

it('generates a class with complex comments', function (): void {
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

    expect($class->generate())->toEqual(basicTemplateGetExpected('class_with_complex_comment'));
});

it('generates a replaced template', function (): void {
    // pre replace
    $class = new Generate('old_class');

    $class->preReplace('class', 'trait');

    expect($class->generate())->toEqual("<?php\n\ntrait old_class\n{\n\n}");

    // replace
    $class->replace(['old_class'], ['new_class']);

    expect($class->generate())->toEqual("<?php\n\ntrait new_class\n{\n\n}");
});
