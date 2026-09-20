# AGENTS.md — Omega MVC Framework

PHP 8.4+ MVC framework library (`Omega\` namespace). Not an application — this is the framework package consumed via Composer.

## Commands

```bash
composer run lint          # phpcs (PSR-12 + 120-col limit, src/ + tests/)
composer run fix           # phpcbf auto-fix
composer run test          # pest (PHPUnit-style Pest classes)
vendor/bin/pest tests/Unit/Macroable   # run a single test file/dir
composer run check         # lint + test
composer run ci            # fix + check (what CI runs)

# Do NOT run PHPStan (phpstan.neon.dist exists, level 10, but is not part of the workflow)
```

Run `lint` before `test`; fix lint errors with `composer run fix` first.

## Code Style

- **PSR-12** with 120-char line limit (comments included, no hard absolute limit)
- camelCase (PSR1.Methods.CamelCapsMethodName excluded in phpcs.xml.dist)
- 4-space indent, UTF-8, LF line endings
- Fixtures directories excluded from linting: `tests/Unit/*/fixtures`
- `declare(strict_types=1);` and a GPL-3.0 "Part of Omega" attribution docblock header in every source/test file

## Structure

- `src/Omega/` — 31 subpackages (Application, Archive, Cache, Collection, Config, Console, Container, Cron, Csrf, Database, DocBlockGenerator, Environment, Event, Exceptions, Facade, Filesystem, Http, Logging, Macroable, Middleware, Queue, RateLimiter, Redis, Router, Security, Session, Testing, Text, Time, Validator, View)
- `tests/Unit/` — mirrors src subpackage names, with an intentional plural: src `Facade` holds only `AbstractFacade`, while tests/Unit `Facades` covers all facades. Every subpackage is mirrored (including `Event`). `tests/Feature/` holds the feature-level integration suites (currently only `Validator`); unit-level Validator tests live in `tests/Unit/Validator`. Pest top-level files in `tests/` (`Pest.php`, `TestCase.php`)
- `tests/Unit/*/fixtures/` — per-subpackage shared fixtures (e.g. `tests/Unit/Http/fixtures/`)
- Global helper files autoloaded via Composer `files`: `Application/helper.php`, `Collection/helper.php`, `Environment/helper.php`, `Http/helper.php`, `Text/helper.php`, `Time/helper.php`, `Validator/helper.php`, `View/helper.php`
- `cache/` — runtime cache (phpcs, phpstan, phpunit, coverage); gitignored
- `phpstan/` — custom PHPStan extensions in the `Omega\PHPStan\` autoload-dev namespace, registered in `phpstan.neon.dist` (PHPStan itself is NOT part of the dev workflow; see Commands)

## Testing

- **Pest 5** on top of PHPUnit. Tests are PHPUnit-style classes that declare coverage via Pest's `covers()` calls (not `#[CoversClass]` attributes); `pest.php` appends `uses()->in('Tests')->group('unit')`
- `composer run test` — no coverage by default (80% minimum via `pest.php` `coverage()->minimum(80)` only when `--coverage` is passed)
- Test env: `APP_ENV=testing`, `OMEGA_TEST_MODE=light` (phpunit.xml.dist)
- phpunit.xml.dist sets `failOnWarning=true`, `failOnRisky=true`, `beStrictAboutOutputDuringTests=true`, `executionOrder="depends,defects"` — stray warnings, risky tests, or echoed output fail the suite. `Filesystem/Util/SizeTest` and `ChecksumTest` deliberately install a temporary error handler so an expected `E_WARNING` doesn't surface (don't remove it)
- `pest.php` references `tests/bootstrap.php` but that file does not exist — bootstrapping happens via `vendor/autoload.php` in phpunit.xml.dist; don't create one
- Coverage HTML: `cache/coverage-report/`; no external services required
- Archive Phar testing quirk: real `Phar` writes are impossible when `phar.readonly=1` (the default; PHP_INI_SYSTEM, not overridable at runtime). `PharAdapter` depends on an injectable `PharEngineInterface` (default `NativePharEngine`); write/delete/rename success+failure paths are tested against in-memory fakes (`FakePharEngine`, `FailingPharEngine`, `UnreadablePharEngine`) with no skip, giving `PharAdapter` 100% lines/branches/paths. `NativePharEngine` tests use `PharData`, which is writable even with `phar.readonly=1`
- `Bz2Adapter::rename()` deliberately avoids a compound `||` guard (separate `if` per condition) so every path is exercised — don't "simplify" it into `||`; it would drop path coverage

## Conventions

- Facades use `AbstractFacade::flushInstance()` at request boundaries
- Container has `setRequestScoped()` / `resetRequestScope()` for persistent-worker support
- Database connections are single long-lived PDO handles (`PDO::ATTR_PERSISTENT => false`); auto-reconnect on lost connection runs only at the `beginTransaction()` boundary (`reconnectIfLost()` in `Database/AbstractConnection.php`, built for RoadRunner-style workers). Don't add a `SELECT 1` ping inside `query()`/`execute()` — it collides with open subquery result sets (SQLSTATE[HY000] 2014)

## Notes

- No CI pipelines configured (no `.github/`)
- `composer.lock` exists on disk but is gitignored and untracked — edit `composer.json` dependencies directly