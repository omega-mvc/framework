<?php

declare(strict_types=1);

namespace Tests\Container;

use ArrayAccess;
use Omega\Container\Attribute\Inject;
use Omega\Container\Container;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Container\Injector;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use stdClass;
use Tests\Container\Support\AnotherService;
use Tests\Container\Support\Dependant;
use Tests\Container\Support\DependencyClass;
use Tests\Container\Support\InjectionUsingAttribute;
use Tests\Container\Support\InjectionUsingAttributeOnParameter;
use Tests\Container\Support\InjectionUsingAttributeOnProperty;
use Tests\Container\Support\MultipleSetterClass;
use Tests\Container\Support\NestedDependencyClass;
use Tests\Container\Support\NonSetterClass;
use Tests\Container\Support\ScalarSetterClass;
use Tests\Container\Support\SetterInjectionClass;
use Tests\Container\Support\StaticSetterClass;
use Tests\Container\Support\UnresolvableSetterClass;
use Tests\TestCase;

#[CoversClass(BindingResolutionException::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(Container::class)]
#[CoversClass(EntryNotFoundException::class)]
#[CoversClass(Inject::class)]
#[CoversClass(Injector::class)]
final class InjectOnTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    public function testCallsSettersWithResolvedDependencies(): void
    {
        $instance = new SetterInjectionClass();
        $this->container->injectOn($instance);

        $this->assertInstanceOf(DependencyClass::class, $instance->dependency);
    }

    public function testSkipsNonSetterMethods(): void
    {
        $instance = new NonSetterClass();
        $this->container->injectOn($instance);

        $this->assertFalse($instance->called);
    }

    public function testInjectsOnlyClassTypes(): void
    {
        $instance = new ScalarSetterClass();
        $this->container->injectOn($instance);

        $this->assertSame('default', $instance->name);
    }

    public function testIgnoresUnresolvableSetters(): void
    {
        $instance = new UnresolvableSetterClass();
        $this->container->injectOn($instance);

        $this->assertNull($instance->dependency);
    }

    public function testSkipsStaticSetters(): void
    {
        StaticSetterClass::$called = false;
        $instance = new class {
        };

        $this->container->injectOn($instance);

        $this->assertFalse(StaticSetterClass::$called);
    }

    public function testInjectsMultipleSetters(): void
    {
        $instance = new MultipleSetterClass();
        $this->container->injectOn($instance);

        $this->assertInstanceOf(DependencyClass::class, $instance->dependency1);
        $this->assertInstanceOf(AnotherService::class, $instance->dependency2);
    }

    public function testResolvesNestedDependencies(): void
    {
        $instance = new NestedDependencyClass();
        $this->container->injectOn($instance);

        $this->assertInstanceOf(Dependant::class, $instance->dependant);

        $dependant = $instance->dependant;

        $this->assertNotSame($dependant, $dependant->dep);
    }

    public function testReturnsTheOriginalInstance(): void
    {
        $instance         = new stdClass();
        $returnedInstance = $this->container->injectOn($instance);

        $this->assertSame($instance, $returnedInstance);
    }

    public function testInjectsThroughTheInjectAttribute(): void
    {
        $instance         = new InjectionUsingAttribute();
        $returnedInstance = $this->container->injectOn($instance);

        $this->assertSame($instance, $returnedInstance);

        $this->assertSame('foo', $returnedInstance->dependency);
    }

    public function testInjectsThroughTheInjectAttributeOnAParameter(): void
    {
        $this->container->set('db.host', 'localhost');
        $instance = new InjectionUsingAttributeOnParameter();
        $returnedInstance = $this->container->injectOn($instance);

        $this->assertSame($instance, $returnedInstance);

        $this->assertSame('localhost', $returnedInstance->dependency);
    }

    public function testInjectsThroughTheInjectAttributeOnAProperty(): void
    {
        $this->container->set('db.host', 'localhost');
        $instance = new InjectionUsingAttributeOnProperty();
        $returnedInstance = $this->container->injectOn($instance);

        $this->assertSame($instance, $returnedInstance);

        $this->assertSame('localhost', $returnedInstance->dependency);
    }

    public function testCatchesBindingResolutionExceptionsDuringMethodInjection(): void
    {
        $instance = new class {
            public bool $resolved = false;

            /**
             * @param ArrayAccess<string, mixed> $dependency
             * @noinspection PhpUnused
             * @noinspection PhpUnusedParameterInspection
             */
            #[Inject]
            public function setDependency(ArrayAccess $dependency): void
            {
                $this->resolved = true;
            }
        };

        $this->container->injectOn($instance);

        $this->assertFalse($instance->resolved);
    }

    public function testCatchesBindingResolutionExceptionsDuringPropertyInjection(): void
    {
        $instance = new class {
            #[Inject(ArrayAccess::class)]
            public string $dependency = 'initial';
        };

        $this->container->injectOn($instance);

        $this->assertSame('initial', $instance->dependency);
    }

    public function testRecognizesInjectableTypes(): void
    {
        $dummy = new class {
            /**
             * @param ArrayAccess<string, mixed> $interface
             */
            public function method(
                string $builtin,
                ArrayAccess $interface,
                int $otherBuiltin
            ): void {
            }
        };

        $params = (new ReflectionClass($dummy))->getMethod('method')->getParameters();

        $method = new ReflectionMethod(Injector::class, 'isTypeInjectable');

        $injector = new Injector($this->container);

        $this->assertFalse((bool) $method->invoke($injector, $params[0]));
        $this->assertTrue((bool) $method->invoke($injector, $params[1]));
        $this->assertFalse((bool) $method->invoke($injector, $params[2]));
    }

    public function testSkipsConstructorsAnnotatedWithInject(): void
    {
        $instance = new class {
            public bool $constructed = true;

            #[Inject]
            public function __construct()
            {
                $this->constructed = true;
            }
        };

        $returnedInstance = $this->container->injectOn($instance);

        $this->assertSame($instance, $returnedInstance);
        $this->assertTrue($instance->constructed);
    }

    public function testResolvesArrayConfiguredInjectionsOnParameters(): void
    {
        $this->container->set('db.host', 'localhost');

        $instance = new class {
            public string $dependency = '';

            #[Inject]
            public function setDependency(#[Inject(['dependency' => 'db.host'])] string $dependency): void
            {
                $this->dependency = $dependency;
            }
        };

        $this->container->injectOn($instance);

        $this->assertSame('localhost', $instance->dependency);
    }

    public function testFallsBackToTheParameterNameForArrayConfiguredInjections(): void
    {
        $this->container->set('dependency', 'fallback');

        $instance = new class {
            public string $dependency = '';

            #[Inject]
            public function setDependency(#[Inject(['other' => 'db.host'])] string $dependency): void
            {
                $this->dependency = $dependency;
            }
        };

        $this->container->injectOn($instance);

        $this->assertSame('fallback', $instance->dependency);
    }

    public function testResolvesAClassTypedParameterWhenTheMethodInjectNameIsAString(): void
    {
        $this->container->set(DependencyClass::class, new DependencyClass());

        $instance = new class {
            public mixed $dependency = null;

            /**
             * A string method-level name is not an array mapping, so the string
             * cannot configure the parameter explicitly and the container falls
             * back to resolving the class-typed parameter.
             */
            #[Inject('unused.string')]
            public function setDependency(DependencyClass $dependency): void
            {
                $this->dependency = $dependency;
            }
        };

        $this->container->injectOn($instance);

        $this->assertInstanceOf(DependencyClass::class, $instance->dependency);
    }

    public function testPrefersTheParameterInjectAttributeOverAStringMethodInjectName(): void
    {
        $this->container->set('db.host', 'localhost');

        $instance = new class {
            public string $dependency = '';

            /**
             * The method-level name is a string, not an array mapping; the
             * parameter-level attribute still wins and resolves the named entry.
             */
            #[Inject('method.config')]
            public function setDependency(#[Inject('db.host')] string $dependency): void
            {
                $this->dependency = $dependency;
            }
        };

        $this->container->injectOn($instance);

        $this->assertSame('localhost', $instance->dependency);
    }

    public function testSkipsArrayConfigurationOnPropertyInjection(): void
    {
        $instance = new class {
            #[Inject(['dependency' => 'db.host'])]
            public string $dependency = 'initial';
        };

        $this->container->injectOn($instance);

        $this->assertSame('initial', $instance->dependency);
    }

    public function testSkipsMethodsWhoseParametersUseUnionTypes(): void
    {
        $instance = new class {
            public bool $called = false;

            #[Inject]
            public function setDependency(stdClass|DependencyClass $dependency): void
            {
                $this->called = true;
            }
        };

        $this->container->injectOn($instance);

        $this->assertFalse($instance->called);
    }
}
