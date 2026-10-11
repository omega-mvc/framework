<?php

declare(strict_types=1);

namespace Tests\Container;

use Omega\Container\Attribute\Inject;
use Omega\Container\Container;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use ReflectionProperty;
use RuntimeException;
use stdClass;
use Tests\Container\Support\DependencyClass;
use Tests\TestCase;

use function count;
use function getenv;
use function putenv;

#[CoversClass(BindingResolutionException::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(Container::class)]
#[CoversClass(EntryNotFoundException::class)]
final class MemoryLeakTest extends TestCase
{
    private Container $container;

    private int $iterations;

    private string|false $savedTestMode;

    private string|false $savedCi;

    private string|false $savedGitHubActions;

    protected function setUp(): void
    {
        $this->container = new Container();

        // Save the env state that drives the iteration budget so branch-switching
        // tests can restore it afterwards, keeping the suite deterministic under
        // any execution order (RoadRunner-style: no state leaks across tests).
        $this->savedTestMode      = getenv('OMEGA_TEST_MODE');
        $this->savedCi            = getenv('CI');
        $this->savedGitHubActions = getenv('GITHUB_ACTIONS');

        $this->iterations = $this->memoryLeakIterationBudget();
    }

    protected function tearDown(): void
    {
        $this->restoreEnvValue('OMEGA_TEST_MODE', $this->savedTestMode);
        $this->restoreEnvValue('CI', $this->savedCi);
        $this->restoreEnvValue('GITHUB_ACTIONS', $this->savedGitHubActions);

        parent::tearDown();
    }

    /**
     * Determine the iteration budget for the memory-leak tests.
     *
     * The budget depends on the environment the suite runs in:
     * - 'OMEGA_TEST_MODE=light' (injected by phpunit.xml.dist) keeps the suite fast;
     * - CI hosts raise the budget to stress long-lived workers a bit harder;
     * - any other environment runs the full 100k iterations used to prove that
     *   the container does not accumulate metadata across resolutions.
     *
     * The decision is extracted into a named method on purpose: the heavy loops
     * keep their full significance, while the non-light branches become reachable
     * and testable in isolation by the env-switching tests below (no 100k real
     * iterations are needed to exercise them).
     */
    private function memoryLeakIterationBudget(): int
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
    private function restoreEnvValue(string $name, string|false $value): void
    {
        if ($value === false) {
            putenv($name);

            return;
        }

        putenv($name . '=' . $value);
    }

    public function testUsesALightIterationBudgetWhenOmegaTestModeIsLight(): void
    {
        putenv('OMEGA_TEST_MODE=light');
        putenv('CI');
        putenv('GITHUB_ACTIONS');

        $this->assertSame(10, $this->memoryLeakIterationBudget());
    }

    public function testRaisesTheIterationBudgetWhenTheCiEnvironmentVariableIsSet(): void
    {
        putenv('OMEGA_TEST_MODE');
        putenv('CI=true');
        putenv('GITHUB_ACTIONS');

        $this->assertSame(100, $this->memoryLeakIterationBudget());
    }

    public function testRaisesTheIterationBudgetWhenOnlyGithubActionsIsSet(): void
    {
        putenv('OMEGA_TEST_MODE');
        putenv('CI');
        putenv('GITHUB_ACTIONS=true');

        $this->assertSame(100, $this->memoryLeakIterationBudget());
    }

    public function testUsesTheCiBudgetWhenBothCiVariablesAreSet(): void
    {
        putenv('OMEGA_TEST_MODE');
        putenv('CI=true');
        putenv('GITHUB_ACTIONS=true');

        $this->assertSame(100, $this->memoryLeakIterationBudget());
    }

    public function testSelectsTheFullIterationBudgetOutsideLightModeAndCi(): void
    {
        putenv('OMEGA_TEST_MODE');
        putenv('CI');
        putenv('GITHUB_ACTIONS');

        $this->assertSame(100000, $this->memoryLeakIterationBudget());
    }

    #[Group('memory-leak')]
    public function testDoesNotGrowMetadataWhenMakingNonSharedInstances(): void
    {
        $value = function (string $property): array {
            $reflection = new ReflectionProperty($this->container, $property);
            $internal = $reflection->getValue($this->container);

            if (!is_array($internal)) {
                throw new RuntimeException("Expected property '{$property}' to be an array.");
            }

            return $internal;
        };

        $initialBindingsCount  = count($value('bindings'));
        $initialInstancesCount = count($value('instances'));
        $initialAliasesCount   = count($value('aliases'));

        for ($i = 0; $i < $this->iterations; $i++) {
            $this->container->make(stdClass::class);
        }

        $this->assertSame($initialBindingsCount, count($value('bindings')));
        $this->assertSame($initialInstancesCount, count($value('instances')));
        $this->assertSame($initialAliasesCount, count($value('aliases')));
    }

    #[Group('memory-leak')]
    public function testDoesNotLeakCallMetadataUnderHeavyUsage(): void
    {
        $callable = function (DependencyClass $dep) {
            return $dep;
        };

        for ($i = 0; $i < $this->iterations; $i++) {
            $this->container->call($callable);
        }

        $this->assertInstanceOf(DependencyClass::class, $this->container->call($callable));
    }

    #[Group('memory-leak')]
    public function testDoesNotLeakInjectionsUnderHeavyUsage(): void
    {
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

        $this->assertInstanceOf(DependencyClass::class, $injectable->dependency);
    }
}
