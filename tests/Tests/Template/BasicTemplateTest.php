<?php

/**
 * Part of Omega - Tests\Template Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Template;

use Omega\Template\Constant;
use Omega\Template\ConstPool;
use Omega\Template\Generate;
use Omega\Template\Method;
use Omega\Template\MethodPool;
use Omega\Template\Property;
use Omega\Template\PropertyPool;
use Omega\Template\Providers\NewConst;
use Omega\Template\Providers\NewFunction;
use Omega\Template\Providers\NewProperty;
use PhpParser\Builder\TraitUse;
use PhpParser\Builder\TraitUseAdaptation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\FixturesPathTrait;

use function file_get_contents;
use function str_replace;

/**
 * Class BasicTemplateTest
 *
 * This test suite verifies the functionality of the Template generation system,
 * which is responsible for producing PHP class structures, docBlocks, and
 * metadata definitions used by the framework’s "make:xxx" commands
 * (controllers, views, facades, and similar).
 *
 * The tests cover generation of classes with varying complexity, including:
 * - basic class definitions,
 * - custom templates,
 * - properties with annotations and expected values,
 * - methods with parameters, return types, and documentation,
 * - constants with comments and assigned values,
 * - trait inclusion,
 * - and complex combinations of these elements.
 *
 * These tests ensure that the Template component can accurately render
 * syntactically correct PHP code and embed structured documentation in a
 * predictable and customizable way.
 *
 * @category  Tests
 * @package   Template
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(Constant::class)]
#[CoversClass(ConstPool::class)]
#[CoversClass(Generate::class)]
#[CoversClass(Method::class)]
#[CoversClass(MethodPool::class)]
#[CoversClass(Property::class)]
#[CoversClass(PropertyPool::class)]
#[CoversClass(NewConst::class)]
#[CoversClass(NewFunction::class)]
#[CoversClass(NewProperty::class)]
final class BasicTemplateTest extends TestCase
{
    use FixturesPathTrait;

    /**
     * Retrieves the expected output fixture for comparison.
     *
     * This helper loads a file from the test's `expected/` directory, normalizes
     * line endings to Unix format, and returns the content as a string. It is used
     * to compare the generated code output against predefined reference templates.
     *
     * @param string $expected The filename of the expected output fixture.
     * @return string The normalized contents of the expected file.
     */
    private function getExpected(string $expected): string
    {
        $fileName = $this->fixturePath(slash(path: '/fixtures/template/') . $expected);

        $fileContent = file_get_contents($fileName);

        return str_replace("\r\n", "\n", $fileContent);
    }

    /**
     * Test it can generate basic class.
     *
     * @return void
     */
    public function testItCanGenerateBasicClass(): void
    {
        $class = new Generate('NewClass');

        $class
            ->setDeclareStrictTypes()
            ->use(Generate::class)
            ->extend(TestCase::class)
            ->implement('testInterface')
            ->setEndWithNewLine();

        $this->assertEquals(
            $this->getExpected('basic_class'),
            $class,
            'this class have parent and interface'
        );
    }

    /**
     * Test it can generate class with trait property and method.
     *
     * @return void
     */
    public function testItCanGenerateClassWithTraitPropertyAndMethod(): void
    {
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
            ->methods(NewFunction::name('test'))
            ->setEndWithNewLine();

        $this->assertEquals(
            $this->getExpected('class_with_trait_property_method'),
            $class->generate(),
            'this class have traits property and method'
        );
    }

    /**
     * Test it can generate class with trait property and method from template.
     *
     * @return void
     */
    public function testItCanGenerateClassWithTraitPropertyAndMethodFromTemplate(): void
    {
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
                NewFunction::name('test')
                    ->customizeTemplate('{{comment}}{{before}}function {{name}}({{params}}){{return type}} {{{new line}}{{body}}{{new line}}}') // phpcs:ignore
            )
            ->setEndWithNewLine();

        $this->assertEquals(
            $this->getExpected('class_with_custom_template'),
            $class->generate(),
            'this class have trait property and method from template'
        );
    }

    /**
     * Test it can generate with complex properties.
     *
     * @return void
     */
    public function testItCanGenerateClassWithComplexProperties(): void
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

        $this->assertEquals(
            $this->getExpected('class_with_complex_property'),
            $class->generate(),
            'this class have complex property'
        );
    }

    /**
     * Test it can generate class with complex method.
     *
     * @return void
     */
    public function testItCanGenerateClassWithComplexMethods(): void
    {
        $class = new Generate('NewClass');

        $class
            ->methods(
                NewFunction::name('test')
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

        $this->assertEquals(
            $this->getExpected('class_with_complex_methods'),
            $class->generate(),
            'this class have complex methods'
        );
    }

    /**
     * Test it can generate class with complex constants.
     *
     * @return void
     */
    public function testItCanGenerateClassWithComplexConstants(): void
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

        // add property using PropertyPool
        $pool = new ConstPool();
        for ($i = 1; $i < 4; $i++) {
            $pool
                ->name('CONSTPOOL_' . $i)
                ->expecting('= true')
            ;
        }
        $class->constants($pool);

        $this->assertEquals(
            $this->getExpected('class_with_complex_const'),
            $class->generate(),
            'this class have complex methods'
        );
    }

    /**
     * Test it can generate class with complex comments.
     *
     * @return void
     */
    public function testItCanGenerateClassWithComplexComments(): void
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

        $this->assertEquals(
            $this->getExpected('class_with_complex_comment'),
            $class->generate(),
            'this class have complex methods'
        );
    }

    /**
     * Test it can generate replaced template.
     *
     * @return void
     */
    public function itCanGenerateReplacedTemplate(): void
    {
        // pre replace
        $class = new Generate('old_class');

        $class->preReplace('class', 'trait');

        $this->assertEquals(
            "<?php\n\ntrait old_class\n{\n\n}",
            $class->generate()
        );

        // replace
        $class->replace(['old_class'], ['new_class']);

        $this->assertEquals(
            "<?php\n\ntrait new_class\n{\n\n}",
            $class->generate()
        );
    }
}
