<?php

/**
 * Part of Omega - Csrf Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Csrf;

use Omega\Container\AbstractServiceProvider;
use Omega\Csrf\CsrfInterface;
use Omega\Session\SessionManager;
use Omega\View\Templator\DirectiveTemplator;
use RuntimeException;

use function sprintf;

class CsrfServiceProvider extends AbstractServiceProvider
{
    public function boot(): void
    {
        $this->app->set(CsrfInterface::class, function (): Csrf {
            $session = $this->app->get('session');

            if (!$session instanceof SessionManager) {
                throw new RuntimeException('The session service is not available.');
            }

            return new Csrf($session);
        });

        $this->app->set('csrf', function (): Csrf {
            $csrf = $this->app->get(CsrfInterface::class);

            if (!$csrf instanceof Csrf) {
                throw new RuntimeException('The csrf service is not available.');
            }

            return $csrf;
        });

        DirectiveTemplator::register('csrf', function (): string {
            $csrf = $this->app->get('csrf');

            if (!$csrf instanceof CsrfInterface) {
                throw new RuntimeException('The csrf service is not available.');
            }

            return sprintf(
                '<input type="hidden" name="%s" value="%s">',
                $csrf->getTokenField(),
                $csrf->getToken()
            );
        });
    }
}
