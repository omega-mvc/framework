<?php

/**
 * Part of Omega - Template Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

/** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */

declare(strict_types=1);

namespace Omega\Template;

use Omega\Template\Traits\CommentTrait;
use Omega\Template\Traits\FormatterTrait;

use function array_filter;
use function call_user_func_array;
use function count;
use function file_put_contents;
use function implode;
use function is_array;
use function is_callable;
use function str_repeat;
use function str_replace;

/**
 * Class responsible for generating PHP class, abstract class, or trait code.
 *
 * This class provides a fluent API to configure:
 * - class/abstract/trait declaration
 * - final modifier
 * - namespace and use statements
 * - extends and implements
 * - traits, constants, properties, methods, and raw body
 * - pre/post string replacements
 * - comments and formatting
 *
 * It integrates FormatterTrait for tab/indent control and CommentTrait
 * for generating docblock comments.
 *
 * @category  Omega
 * @package   Template
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class Generate
{
    use FormatterTrait;
    use CommentTrait;

    /** @var int Constant representing a regular class declaration. */
    public const int SET_CLASS = 0;

    /** @var int Constant representing an abstract class declaration. */
    public const int SET_ABSTRACT = 1;

    /** @var int Constant representing a trait declaration. */
    public const int SET_TRAIT = 2;

    /** @var bool Whether the class/trait is marked as final. */
    private bool $isFinal = false;

    /** @var int Rule for type of generation: class, abstract class, or trait. */
    private int $rule;

    /** @var bool Whether the generated file ends with a newline. */
    private bool $endWithNewline = false;

    /** @var string|null Name of the class/trait being generated. */
    private ?string $name;

    /** @var string[] PHP declare directives to add at the beginning of the file. */
    private array $declare = [];

    /** @var string|null Namespace for the generated class/trait. */
    private ?string $namespace = null;

    /** @var string[] List of use statements for the generated file. */
    private array $uses = [];

    /** @var string|null Class or trait to extend. */
    private ?string $extend = null;

    /** @var string[] List of interfaces implemented by the class. */
    private array $implements = [];

    /** @var string[] List of traits used in the generated class. */
    private array $traits = [];

    /** @var string[] List of constants added to the generated class. Can contain Constant objects or strings. */
    private array $constants = [];

    /** @var string[] List of properties added to the generated class. Can contain Property objects or strings. */
    private array $properties = [];

    /** @var string[] List of methods added to the generated class. Can contain Method objects or strings. */
    private array $methods = [];

    /** @var string[] Raw body lines added to the generated class. */
    private array $body = [];

    /** @var string [][] Pre-replacement search and replace arrays. [0] => search patterns [1] => replacement strings */
    private array $preReplace = [[], []];

    /**
     * Replacement search and replace arrays applied after generation.
     *
     * [0] => search patterns
     * [1] => replacement strings
     *
     * @var string[][]
     */
    private array $replace = [[], []];

    /**
     * Constructor.
     *
     * @param string $name Name of the class/trait to generate. Defaults to 'NewClass'.
     * @return void
     */
    public function __construct(string $name = 'NewClass')
    {
        $this->name = $name;

        $this->rule = Generate::SET_CLASS;
    }

    /**
     * Invokes the generator object as a function to set the class/trait name.
     *
     * @param string $name Name of the class or trait to generate.
     *
     * @return self Returns the current instance for fluent chaining.
     */
    public function __invoke(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Static factory method to create a new generator instance with a given name.
     *
     * @param string $name Name of the class or trait to generate.
     *
     * @return self Returns a new instance of Generate.
     */
    public static function static(string $name): self
    {
        return new self($name);
    }

    /**
     * Converts the generator object to a string by generating the PHP code.
     *
     * @return string The generated PHP code as a string.
     */
    public function __toString(): string
    {
        return $this->generate();
    }

    /**
     * Returns the template for generating the PHP class/abstract class/trait code.
     *
     * If a custom template has been set via `customizeTemplate()`, it will be used.
     * Otherwise, a default PHP class template is returned.
     *
     * @return string The raw template string for code generation.
     */
    private function planTemplate(): string
    {
        return $this->customizeTemplate
            ?? "<?php\n{{before}}{{comment}}\n{{rule}}class\40{{head}}\n{\n{{body}}\n}{{end}}";
    }

    /**
     * Generates the PHP code for the class/abstract class/trait with all configured elements.
     *
     * This method processes:
     * - namespace and use statements
     * - declare directives
     * - class rule (abstract/final)
     * - extends and implements
     * - traits, constants, properties, methods
     * - raw body lines
     * - pre- / post-replacements
     *
     * @return string The fully generated PHP code.
     */
    public function generate(): string
    {
        // pre replace
        $class = str_replace(
            $this->preReplace[0],
            $this->preReplace[1],
            $this->planTemplate()
        );

        $tabDept = fn (int $dept) => str_repeat($this->tabIndent, $dept * $this->tabSize);

        // scope: before
        $before = [];
        if ($this->namespace !== null || count($this->uses) > 0 || count($this->declare) > 0) {
            $before[] = '';
        }

        if (count($this->declare) > 0) {
            foreach ($this->declare as $declare => $value) {
                $before[] = "declare({$declare}={$value});\n";
            }
        }

        // generate namespace
        if ($this->namespace !== null) {
            $before[] = 'namespace ' . $this->namespace . ";\n";
        }

        // generate uses
        if (count($this->uses) > 0) {
            $before[] = 'use ' . implode(";\nuse ", $this->uses) . ';';
            $before[] = '';
        }

        // scope comment, generate comment
        if ('' !== ($comment = $this->generateComment(0, $this->tabIndent))) {
            $before[] = '';
        }

        // built before
        $before = implode("\n", $before);

        // generate class rule
        $rule = $this->rule == 0
            ? ''
            : $this->ruleText() . ' ';

        // generate final
        $rule = !$this->isFinal
            ? $rule
            : 'final ' . $rule;

        // scope: head
        // generate class name
        $head = [];

        // generate class name
        $head[] = $this->name;

        // generate extend
        if ($this->extend !== null) {
            $head[] = 'extends ' . $this->extend;
        }

        // generate implements
        if (count($this->implements) > 0) {
            $head[] = 'implements ' . implode(', ', $this->implements);
        }

        $head = implode(' ', $head);

        // scope: body
        $body = [];
        // generate traits
        if (count($this->traits) > 0) {
            $body[] = $tabDept(1) . 'use ' . implode(', ', $this->traits) . ";\n";
        }

        // generate constants
        $constants = [];
        if (count($this->constants) > 0) {
            foreach ($this->constants as $const) {
                /* @phpstan-ignore-next-line */
                if ($const instanceof Constant) {
                    $const
                        ->tabSize($this->tabSize)
                        ->tabIndent($this->tabIndent);
                    $constants[] = $tabDept(1) . $const->generate();
                }
            }

            $constants[] = '';
        }
        $body[] = implode("\n", $constants);

        // generate property
        $properties = [];
        if (count($this->properties) > 0) {
            foreach ($this->properties as $property) {
                /* @phpstan-ignore-next-line */
                if ($property instanceof Property) {
                    $property
                        ->tabSize($this->tabSize)
                        ->tabIndent($this->tabIndent);
                    $properties[] = $tabDept(1) . $property->generate();
                }
            }

            $properties[] = '';
        }
        $body[] = implode("\n", $properties);

        // generate functions
        $methods = [];
        if (count($this->methods) > 0) {
            foreach ($this->methods as $method) {
                /* @phpstan-ignore-next-line */
                if ($method instanceof Method) {
                    $method
                        ->tabSize($this->tabSize)
                        ->tabIndent($this->tabIndent);
                    $methods[] = $tabDept(1) . $method->generate();
                }
            }

            $methods[] = '';
        }
        $body[] = implode("\n\n", array_filter($methods));

        // generate raw body
        if (count($this->body) > 0) {
            $body[] = $tabDept(1) . implode("\n" . $tabDept(2), $this->body);
        }

        $body = implode("\n", array_filter($body));

        // end with new line
        $end = $this->endWithNewline ? "\n" : '';

        // manual replace
        $search  = $this->replace[0] ?? null;
        $replace = $this->replace[1] ?? null;

        return str_replace(
            ['{{before}}', '{{comment}}', '{{rule}}', '{{head}}', '{{body}}', '{{end}}', ...$search],
            [$before, $comment, $rule, $head, $body, $end, ...$replace],
            $class
        );
    }

    /**
     * Returns the textual representation of the class rule.
     *
     * Determines whether the class should be generated as `abstract`, `trait`, or a normal class
     * based on the current rule value.
     *
     * @return string The keyword for the class rule: 'abstract', 'trait', or an empty string for a normal class.
     */
    private function ruleText(): string
    {
        return match ($this->rule) {
            self::SET_CLASS, self::SET_ABSTRACT => 'abstract',
            self::SET_TRAIT => 'trait',
            default         => '',
        };
    }

    /**
     * Saves the generated PHP code to a file.
     *
     * @param string $pathToSave The directory path where the PHP file should be saved.
     * @return int|false Returns the number of bytes written on success, or false on failure.
     */
    public function save(string $pathToSave): int|false
    {
        return file_put_contents($pathToSave . '/' . $this->name . '.php', $this->generate());
    }

    /**
     * Sets the rule for this generator (class, abstract class, or trait).
     *
     * @param int $rule One of the class constants: SET_CLASS, SET_ABSTRACT, SET_TRAIT.
     *
     * @return self Returns the current instance for fluent chaining.
     */
    public function rule(int $rule = self::SET_CLASS): self
    {
        $this->rule = $rule;

        return $this;
    }

    /**
     * Marks the class/trait as final or not.
     *
     * @param bool $isFinal Set to true to generate the class/trait as final, false otherwise.
     * @return self Returns the current instance for fluent chaining.
     */
    public function setFinal(bool $isFinal = true): self
    {
        $this->isFinal = $isFinal;

        return $this;
    }

    /**
     * Sets whether the generated PHP code should end with a newline character.
     *
     * @param bool $enable Set to true to append a newline at the end of the generated file.
     * @return self Returns the current instance for fluent chaining.
     */
    public function setEndWithNewLine(bool $enable = true): self
    {
        $this->endWithNewline = $enable;

        return $this;
    }

    /**
     * Adds a PHP `declare` directive to the generated file.
     *
     * Supported directives include:
     * - ticks
     * - encoding
     * - strict_types
     *
     * @param string $directive The directive name (e.g., 'strict_types').
     * @param string|int $value The value for the directive (integer or string).
     * @return self Returns the current instance for fluent chaining.
     */
    public function addDeclare(string $directive, string|int $value): self
    {
        $this->declare[$directive] = $value;

        return $this;
    }

    /**
     * Convenience method to set the `strict_types` declare directive.
     *
     * @param int $level The strict types level, usually 1 to enable strict types.
     * @return self Returns the current instance for fluent chaining.
     */
    public function setDeclareStrictTypes(int $level = 1): self
    {
        return $this->addDeclare('strict_types', $level);
    }

    // setter

    /**
     * Sets the name of the class or trait to be generated.
     *
     * @param string $name The name of the class or trait.
     * @return self Returns the current instance for fluent chaining.
     */
    public function name(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Sets the namespace for the generated class or trait.
     *
     * @param string $namespace The namespace to apply to the generated code.
     * @return self Returns the current instance for fluent chaining.
     */
    public function namespace(string $namespace): self
    {
        $this->namespace = $namespace;

        return $this;
    }

    /**
     * Adds a single `use` statement for importing a namespace.
     *
     * @param string $useNamespace The fully qualified namespace to import.
     * @return self Returns the current instance for fluent chaining.
     */
    public function use(string $useNamespace): self
    {
        $this->uses[] = $useNamespace;

        return $this;
    }

    /**
     * Sets multiple `use` statements for importing namespaces.
     *
     * @param string[] $usesNamespace An array of fully qualified namespaces to import.
     * @return self Returns the current instance for fluent chaining.
     */
    public function uses(array $usesNamespace): self
    {
        $this->uses = $usesNamespace;

        return $this;
    }

    /**
     * Sets the class or trait that the generated class should extend.
     *
     * @param string $extend The parent class name.
     * @return self Returns the current instance for fluent chaining.
     */
    public function extend(string $extend): self
    {
        $this->extend = $extend;

        return $this;
    }

    /**
     * Adds a single interface that the generated class should implement.
     *
     * @param string $implement The interface name to implement.
     * @return self Returns the current instance for fluent chaining.
     */
    public function implement(string $implement): self
    {
        $this->implements[] = $implement;

        return $this;
    }

    /**
     * Sets multiple interfaces that the generated class should implement.
     *
     * @param string[] $implements An array of interface names.
     * @return self Returns the current instance for fluent chaining.
     */
    public function implements(array $implements): self
    {
        $this->implements = $implements;

        return $this;
    }

    /**
     * Adds a single trait to be used by the generated class.
     *
     * @param string $trait The trait name to include.
     * @return self Returns the current instance for fluent chaining.
     */
    public function trait(string $trait): self
    {
        $this->traits[] = $trait;

        return $this;
    }

    /**
     * Sets multiple traits to be used by the generated class.
     *
     * @param string[] $traits An array of trait names.
     * @return self Returns the current instance for fluent chaining.
     */
    public function traits(array $traits): self
    {
        $this->traits = $traits;

        return $this;
    }

    /**
     * Adds a raw string to the body of the generated class.
     *
     * @param string $rawBody The raw PHP code to include in the class body.
     * @return self Returns the current instance for fluent chaining.
     */
    public function body(string $rawBody): self
    {
        $this->body[] = $rawBody;

        return $this;
    }

    /**
     * Adds a new constant with a default or custom name.
     *
     * @param string $name The name of the constant to add. Defaults to 'NEW_CONST'.
     * @return Constant Returns the newly created Constant instance.
     */
    public function addConst(string $name = 'NEW_CONST'): Constant
    {
        return $this->constants[] = new Constant($name);
    }

    /**
     * Adds one or more constants to the generated class.
     *
     * @param callable(ConstPool): void|Constant|ConstPool $newConst Either:
     *      - a single Constant instance,
     *      - a ConstPool instance containing multiple constants,
     *      - or a callable that receives a ConstPool to define multiple constants.
     * @return self Returns the current instance for fluent chaining.
     */
    public function constants(callable|ConstPool|Constant $newConst): self
    {
        // detect if single const
        if ($newConst instanceof Constant) {
            $this->constants[] = $newConst;
        } elseif (is_callable($newConst)) { // detect if multi const with constPool
            $const = new ConstPool();

            call_user_func_array($newConst, [$const]);

            foreach ($const->getPools() as $pool) {
                if ($pool instanceof Constant) {
                    $this->constants[] = $pool;
                }
            }
        } elseif ($newConst instanceof ConstPool) { // detect parameter is instance constPool
            foreach ($newConst->getPools() as $pool) {
                if ($pool instanceof Constant) {
                    $this->constants[] = $pool;
                }
            }
        }

        return $this;
    }

    /**
     * Adds a new property with a default or custom name.
     *
     * @param string $name The name of the property. Defaults to 'new_property'.
     *
     * @return Property Returns the newly created Property instance.
     */
    public function addProperty(string $name = 'new_property'): Property
    {
        return $this->properties[] = new Property($name);
    }

    /**
     * Adds one or more properties to the generated class.
     *
     * @param callable(PropertyPool): void|Property|PropertyPool $newProperty Either:
     *      - a single Property instance,
     *      - a PropertyPool instance containing multiple properties,
     *      - or a callable that receives a PropertyPool to define multiple properties.
     * @return self Returns the current instance for fluent chaining.
     */
    public function properties(callable|Property|PropertyPool $newProperty): self
    {
        // detect if single properties
        if ($newProperty instanceof Property) {
            $this->properties[] = $newProperty;
        } elseif (is_callable($newProperty)) { // detect if multi property with propertyPool
            $property = new PropertyPool();

            call_user_func_array($newProperty, [$property]);

            foreach ($property->getPools() as $pool) {
                if ($pool instanceof Property) {
                    $this->properties[] = $pool;
                }
            }
        } elseif ($newProperty instanceof PropertyPool) { // detect parameter is instance methodPool
            foreach ($newProperty->getPools() as $pool) {
                if ($pool instanceof Property) {
                    $this->properties[] = $pool;
                }
            }
        }

        return $this;
    }

    /**
     * Adds a new method with a default or custom name.
     *
     * @param string $name The name of the method. Defaults to 'new_method'.
     * @return Method Returns the newly created Method instance.
     */
    public function addMethod(string $name = 'new_method'): Method
    {
        return $this->methods[] = new Method($name);
    }

    /**
     * Adds one or more methods to the generated class.
     *
     * @param callable(MethodPool): void|Method|MethodPool $newMethod Either:
     *      - a single Method instance,
     *      - a MethodPool instance containing multiple methods,
     *      - or a callable that receives a MethodPool to define multiple methods.
     * @return self Returns the current instance for fluent chaining.
     */
    public function methods(MethodPool|callable|Method $newMethod): self
    {
        // detect if single properties
        if ($newMethod instanceof Method) {
            $this->methods[] = $newMethod;
        } elseif (is_callable($newMethod)) { // detect if multi property with methodsPool
            $method = new MethodPool();

            call_user_func_array($newMethod, [$method]);

            foreach ($method->getPools() as $pool) {
                if ($pool instanceof Method) {
                    $this->methods[] = $pool;
                }
            }
        } elseif ($newMethod instanceof MethodPool) { // detect parameter is instance methodPool
            foreach ($newMethod->getPools() as $pool) {
                if ($pool instanceof Method) {
                    $this->methods[] = $pool;
                }
            }
        }

        return $this;
    }

    /**
     * Sets search-and-replace pairs to apply before generating the class.
     *
     * @param string|string[] $search The text(s) to search for.
     * @param string|string[] $replace The text(s) to replace with.
     * @return self Returns the current instance for fluent chaining.
     */
    public function preReplace(array|string $search, array|string $replace): self
    {
        $search  = is_array($search) ? $search : [$search];
        $replace = is_array($replace) ? $replace : [$replace];

        $this->preReplace = [$search, $replace];

        return $this;
    }

    /**
     * Sets search-and-replace pairs to apply after generating the class.
     *
     * @param string|string[] $search The text(s) to search for.
     * @param string|string[] $replace The text(s) to replace with.
     *
     * @return self Returns the current instance for fluent chaining.
     */
    public function replace(array|string $search, array|string $replace): self
    {
        $search  = is_array($search) ? $search : [$search];
        $replace = is_array($replace) ? $replace : [$replace];

        $this->replace = [$search, $replace];

        return $this;
    }
}
