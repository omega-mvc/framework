<?php

declare(strict_types=1);

namespace Omega\Security;

use Omega\Container\Exceptions\CircularAliasException;
use Omega\Security\Hashing\Argon2IdHasher;
use Omega\Security\Hashing\ArgonHasher;
use Omega\Security\Hashing\BcryptHasher;
use Omega\Security\Hashing\DefaultHasher;
use Omega\Security\Hashing\HashInterface;
use Omega\Security\Hashing\HashManager;
use Omega\Container\AbstractServiceProvider;
use Omega\Config\Facade\Config;

use function is_int;

class HashServiceProvider extends AbstractServiceProvider
{
    /**
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     */
    public function boot(): void
    {
        $this->app->set('hash.bcrypt', function (): BcryptHasher {
            $rounds = Config::get('BCRYPT_ROUNDS', 12);

            return new BcryptHasher()
                ->setRounds(is_int($rounds) ? $rounds : 12);
        });
        $this->app->set('hash.argon', value: function (): ArgonHasher {
            return new ArgonHasher()
                ->setMemory(1024)
                ->setTime(2)
                ->setThreads(2);
        });
        $this->app->set('hash.argon2id', fn (): Argon2IdHasher => new Argon2IdHasher());
        $this->app->set('hash.default', fn (): DefaultHasher => new DefaultHasher());

        $this->app->set('hash', function (): HashManager {
            /** @var HashInterface $bcrypt */
            $bcrypt = $this->app->get('hash.bcrypt');
            /** @var HashInterface $argon */
            $argon = $this->app->get('hash.argon');
            /** @var HashInterface $argon2id */
            $argon2id = $this->app->get('hash.argon2id');
            /** @var HashInterface $default */
            $default = $this->app->get('hash.default');

            $hash = new HashManager();
            $hash->setDefaultDriver($bcrypt);
            $hash->setDriver('bcrypt', $bcrypt);
            $hash->setDriver('argon', $argon);
            $hash->setDriver('argon2id', $argon2id);
            $hash->setDriver('default', $default);

            return $hash;
        });
    }
}
