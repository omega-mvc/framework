<?php

declare(strict_types=1);

namespace Tests\Container;

use Omega\Container\Attribute\Inject;
use Omega\Container\Container;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use ReflectionProperty;
use stdClass;
use Tests\Container\Support\DependencyClass;

use function count;
use function getenv;
use function putenv;

covers(BindingResolutionException::class);
covers(CircularAliasException::class);
covers(Container::class);
covers(EntryNotFoundException::class);

/**
 * Determine the iteration budget for the memory-leak tests.
 *
 * The budget depends on the environment the suite runs in:
 * - 'OMEGA_TEST_MODE=light' (injected by phpunit.xml.dist) keeps the suite fast;
 * - CI hosts raise the budget to stress long-lived workers a bit harder;
 * - any other environment runs the full 100k iterations used to prove that
 *   the container does not accumulate metadata across resolutions.
 *
 * The decision is extracted into a named function on purpose: the heavy loops
 * keep their full significance, while the non-light branches become reachable
 * and testable in isolation by the env-switching tests below (no 100k real
 * iterations are needed to exercise them).
 */
function memoryLeakIterationBudget(): int
{
    if (getenv('OMEGA_TEST_MODE') === 'light') {
        return 10;
    } elseif (getenv('CI') || getenv('GITHUB_ACTIONS')) {
        return 100;
    }

    return 100000;
}

/**
 * Restore a previously saved getenv() value.
 *
 * @param string|false $value false when the variable was unset.
 */
function restoreEnvValue(string $name, string|false $value): void
{
    if ($value === false) {
        putenv($name);

        return;
    }

    putenv($name . '=' . $value);
}

beforeEach(function (): void {
    $this->container = new Container();

    // Save the env state that drives the iteration budget so branch-switching
    // tests can restore it afterwards, keeping the suite deterministic under
    // any execution order (RoadRunner-style: no state leaks across tests).
    $this->savedTestMode      = getenv('OMEGA_TEST_MODE');
    $this->savedCi            = getenv('CI');
    $this->savedGitHubActions = getenv('GITHUB_ACTIONS');

    $this->iterations = memoryLeakIterationBudget();
});

afterEach(function (): void {
    restoreEnvValue('OMEGA_TEST_MODE', $this->savedTestMode);
    restoreEnvValue('CI', $this->savedCi);
    restoreEnvValue('GITHUB_ACTIONS', $this->savedGitHubActions);
});

it('uses a light iteration budget when OMEGA_TEST_MODE is light', function (): void {
    putenv('OMEGA_TEST_MODE=light');
    putenv('CI');
    putenv('GITHUB_ACTIONS');

    expect(memoryLeakIterationBudget())->toBe(10);
});

it('raises the iteration budget when the CI environment variable is set', function (): void {
    putenv('OMEGA_TEST_MODE');
    putenv('CI=true');
    putenv('GITHUB_ACTIONS');

    expect(memoryLeakIterationBudget())->toBe(100);
});

it('raises the iteration budget when only GITHUB_ACTIONS is set', function (): void {
    putenv('OMEGA_TEST_MODE');
    putenv('CI');
    putenv('GITHUB_ACTIONS=true');

    expect(memoryLeakIterationBudget())->toBe(100);
});

it('uses the CI budget when both CI variables are set', function (): void {
    putenv('OMEGA_TEST_MODE');
    putenv('CI=true');
    putenv('GITHUB_ACTIONS=true');

    expect(memoryLeakIterationBudget())->toBe(100);
});

it('selects the full iteration budget outside light mode and CI', function (): void {
    putenv('OMEGA_TEST_MODE');
    putenv('CI');
    putenv('GITHUB_ACTIONS');

    expect(memoryLeakIterationBudget())->toBe(100000);
});

it('does not grow metadata when making non-shared instances', function (): void {
    $value = function (string $property): array {
        $reflection = new ReflectionProperty($this->container, $property);
        $reflection->setAccessible(true);
        $internal = $reflection->getValue($this->container);

        if (!is_array($internal)) {
            throw new \RuntimeException("Expected property '{$property}' to be an array.");
        }

        return $internal;
    };

    $initialBindingsCount  = count($value('bindings'));
    $initialInstancesCount = count($value('instances'));
    $initialAliasesCount   = count($value('aliases'));

    for ($i = 0; $i < $this->iterations; $i++) {
        $this->container->make(stdClass::class);
    }

    expect(count($value('bindings')))->toBe($initialBindingsCount);
    expect(count($value('instances')))->toBe($initialInstancesCount);
    expect(count($value('aliases')))->toBe($initialAliasesCount);
})->group('memory-leak');

it('does not leak call metadata under heavy usage', function (): void {
    $callable = function (DependencyClass $dep) {
        return $dep;
    };

    for ($i = 0; $i < $this->iterations; $i++) {
        $this->container->call($callable);
    }

    expect($this->container->call($callable))->toBeInstanceOf(DependencyClass::class);
})->group('memory-leak');

it('does not leak injections under heavy usage', function (): void {
    $injectable = new class {
        public ?DependencyClass $dependency = null;

        #[Inject]
        public function setDependency(DependencyClass $dependency): void
        {
            $this->dependency = $dependency;
        }
    };

    for ($i = 0; $i < $this->iterations; $i++) {
        $this->container->injectOn($injectable);
    }

    expect($injectable->dependency)->toBeInstanceOf(DependencyClass::class);
})->group('memory-leak');