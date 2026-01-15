<?php

/**
 * Part of Omega - Tests\Container Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Container;

use Omega\Container\Container;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;

/**
 * Abstract base class for container-related test support.
 *
 * Provides a pre-initialized container instance and helper methods for accessing
 * protected methods and properties of the container.
 *
 * @category  Tests
 * @package   Container
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversNothing]
abstract class AbstractTestContainer extends TestCase
{
    /** @var Container|null Container instance for test support, accessible by child tests. */
    protected ?Container $container;

    /**
     * Sets up the environment before each test method.
     *
     * This method is called automatically by PHPUnit before each test runs.
     * It is responsible for initializing the application instance, setting up
     * dependencies, and preparing any state required by the test.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->container = new Container();
    }

    /**
     * Tears down the environment after each test method.
     *
     * This method is called automatically by PHPUnit after each test runs.
     * It is responsible for cleaning up resources, flushing the application
     * state, unsetting properties, and resetting any static or global state
     * to avoid side effects between tests.
     *
     * @return void
     */
    public function tearDown(): void
    {
        $this->container = null;
    }

    /**
     * Call protected.
     *
     * @param string $methodName Holds the method name.
     * @param array  $args Holds an array of arguments.
     * @return mixed
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     * @noinspection PhpExpressionResultUnusedInspection
     */
    public function callProtected(string $methodName, array $args = []): mixed
    {
        $reflection = new ReflectionClass($this->container);
        $method     = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($this->container, $args);
    }

    /**
     * Get protected property.
     *
     * @param string $propertyName Holds the property name.
     * @return mixed
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     * @noinspection PhpExpressionResultUnusedInspection
     */
    public function getProtectedProperty(string $propertyName): mixed
    {
        $reflection = new ReflectionClass($this->container);
        $property   = $reflection->getProperty($propertyName);
        $property->setAccessible(true);

        return $property->getValue($this->container);
    }
}
