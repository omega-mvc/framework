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
use ReflectionClass;
use ReflectionMethod;
use stdClass;
use Tests\Container\Support\AnotherService;
use Tests\Container\Support\Dependant;
use Tests\Container\Support\Dependency;
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

covers(BindingResolutionException::class);
covers(CircularAliasException::class);
covers(Container::class);
covers(EntryNotFoundException::class);
covers(Inject::class);
covers(Injector::class);

beforeEach(function (): void {
    $this->container = new Container();
});

it('calls setters with resolved dependencies', function (): void {
    $instance = new SetterInjectionClass();
    $this->container->injectOn($instance);

    expect($instance->dependency)->toBeInstanceOf(DependencyClass::class);
});

it('skips non-setter methods', function (): void {
    $instance = new NonSetterClass();
    $this->container->injectOn($instance);

    expect($instance->called)->toBeFalse();
});

it('injects only class types', function (): void {
    $instance = new ScalarSetterClass();
    $this->container->injectOn($instance);

    expect($instance->name)->toBe('default');
});

it('ignores unresolvable setters', function (): void {
    $instance = new UnresolvableSetterClass();
    $this->container->injectOn($instance);

    expect($instance->dependency)->toBeNull();
});

it('skips static setters', function (): void {
    StaticSetterClass::$called = false;
    $instance = new class {
    };

    $this->container->injectOn($instance);

    expect(StaticSetterClass::$called)->not->toBeTrue();
});

it('injects multiple setters', function (): void {
    $instance = new MultipleSetterClass();
    $this->container->injectOn($instance);

    expect($instance->dependency1)->toBeInstanceOf(DependencyClass::class);
    expect($instance->dependency2)->toBeInstanceOf(AnotherService::class);
});

it('resolves nested dependencies', function (): void {
    $instance = new NestedDependencyClass();
    $this->container->injectOn($instance);

    expect($instance->dependant)->toBeInstanceOf(Dependant::class);

    $dependant = $instance->dependant;

    if (!$dependant instanceof Dependant) {
        throw new \RuntimeException('Expected a Dependant instance.');
    }

    expect($dependant->dep)->not->toBe($dependant);
});

it('returns the original instance', function (): void {
    $instance         = new stdClass();
    $returnedInstance = $this->container->injectOn($instance);

    expect($returnedInstance)->toBe($instance);
});

it('injects through the Inject attribute', function (): void {
    $instance         = new InjectionUsingAttribute();
    $returnedInstance = $this->container->injectOn($instance);

    expect($returnedInstance)->toBe($instance);

    if (!$returnedInstance instanceof InjectionUsingAttribute) {
        throw new \RuntimeException('Expected an InjectionUsingAttribute instance.');
    }

    expect($returnedInstance->dependency)->toBe('foo');
});

it('injects through the Inject attribute on a parameter', function (): void {
    $this->container->set('db.host', 'localhost');
    $instance = new InjectionUsingAttributeOnParameter();
    $returnedInstance = $this->container->injectOn($instance);

    expect($returnedInstance)->toBe($instance);

    if (!$returnedInstance instanceof InjectionUsingAttributeOnParameter) {
        throw new \RuntimeException('Expected an InjectionUsingAttributeOnParameter instance.');
    }

    expect($returnedInstance->dependency)->toBe('localhost');
});

it('injects through the Inject attribute on a property', function (): void {
    $this->container->set('db.host', 'localhost');
    $instance = new InjectionUsingAttributeOnProperty();
    $returnedInstance = $this->container->injectOn($instance);

    expect($returnedInstance)->toBe($instance);

    if (!$returnedInstance instanceof InjectionUsingAttributeOnProperty) {
        throw new \RuntimeException('Expected an InjectionUsingAttributeOnProperty instance.');
    }

    expect($returnedInstance->dependency)->toBe('localhost');
});

it('catches binding resolution exceptions during method injection', function (): void {
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

    expect($instance->resolved)->toBeFalse();
});

it('catches binding resolution exceptions during property injection', function (): void {
    $instance = new class {
        #[Inject(ArrayAccess::class)]
        public string $dependency = 'initial';
    };

    $this->container->injectOn($instance);

    expect($instance->dependency)->toBe('initial');
});

it('recognizes injectable types', function (): void {
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
    $method->setAccessible(true);

    $injector = new Injector($this->container);

    expect($method->invoke($injector, $params[0]))->toBeFalse();
    expect($method->invoke($injector, $params[1]))->toBeTrue();
    expect($method->invoke($injector, $params[2]))->toBeFalse();
});

it('skips constructors annotated with Inject', function (): void {
    $instance = new class {
        public bool $constructed = true;

        #[Inject]
        public function __construct()
        {
            $this->constructed = true;
        }
    };

    $returnedInstance = $this->container->injectOn($instance);

    expect($returnedInstance)->toBe($instance);
    expect($instance->constructed)->toBeTrue();
});

it('resolves array-configured injections on parameters', function (): void {
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

    expect($instance->dependency)->toBe('localhost');
});

it('falls back to the parameter name for array-configured injections', function (): void {
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

    expect($instance->dependency)->toBe('fallback');
});

it('resolves a class-typed parameter when the method Inject name is a string', function (): void {
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

    expect($instance->dependency)->toBeInstanceOf(DependencyClass::class);
});

it('prefers the parameter Inject attribute over a string method Inject name', function (): void {
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

    expect($instance->dependency)->toBe('localhost');
});

it('skips array-configuration on property injection', function (): void {
    $instance = new class {
        #[Inject(['dependency' => 'db.host'])]
        public string $dependency = 'initial';
    };

    $this->container->injectOn($instance);

    expect($instance->dependency)->toBe('initial');
});

it('skips methods whose parameters use union types', function (): void {
    $instance = new class {
        public bool $called = false;

        #[Inject]
        public function setDependency(stdClass|DependencyClass $dependency): void
        {
            $this->called = true;
        }
    };

    $this->container->injectOn($instance);

    expect($instance->called)->toBeFalse();
});
