<?php

/**
 * Part of Omega - Tests\Support\Facades Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Support\Facades;

use Exception;
use Omega\Application\Application;
use Omega\Collection\Collection;
use Omega\Support\Facades\AbstractFacade;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Support\Facades\Sample\FacadesTestClass;
use Throwable;

/**
 * Tests the behavior of the facade system.
 *
 * This test suite verifies that facades correctly resolve their underlying
 * instances from the application container and proxy static method calls to
 * the resolved object. It also ensures that appropriate errors are thrown
 * when the facade base application has not been initialized.
 *
 * @category   Tests
 * @package    Support
 * @subpackage Facades
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[CoversClass(AbstractFacade::class)]
#[CoversClass(Application::class)]
#[CoversClass(Collection::class)]
final class FacadeTest extends TestCase
{
    /**
     * Test it can call static.
     *
     * @return void
     * @throws Exception
     */
    final public function testItCanCallStatic(): void
    {
        $app = new Application(basePath: __DIR__);
        $app->set(Collection::class, fn () => new Collection(['php' => 'greater']));

        AbstractFacade::setFacadeBase($app);

        $this->assertTrue(FacadesTestClass::has('php'));
        $app->flush();
        AbstractFacade::flushInstance();
    }

    /**
     * Test it throw error when application is not set.
     *
     * @return void
     */
    public function testItThrowErrorWhenApplicationIsNotSet(): void
    {
        AbstractFacade::flushInstance();
        AbstractFacade::setFacadeBase();
        try {
            FacadesTestClass::has('php');
        } catch (Throwable $th) {
            $this->assertEquals('Call to a member function make() on null', $th->getMessage());
        }
    }
}
