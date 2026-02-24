<?php

/**
 * Part of Omega - Tests\Support\Bootstrap Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Support\Bootstrap\Support;

use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Container\Provider\AbstractServiceProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;

/**
 * Class TestServiceProvider
 *
 * A test-only service provider used to verify the container registration behavior.
 *
 * This provider is intentionally idempotent and rebinds an already existing
 * service ("ping") during the register phase. It is specifically used to ensure
 * that registering the same service provider multiple times does not cause
 * duplicated registrations, unexpected overrides, or container side effects.
 *
 * The register method retrieves an existing binding and re-sets it into the
 * container, allowing tests to assert that duplicate provider registration
 * does not alter application state.
 *
 * @category   Tests
 * @package    Support
 * @subpackage Bootstrap\Support
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[CoversClass(AbstractServiceProvider::class)]
#[CoversClass(BindingResolutionException::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(EntryNotFoundException::class)]
class TestServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     *
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function register(): void
    {
        $ping = $this->app->get('ping');

        $this->app->set('ping', $ping);
    }
}
