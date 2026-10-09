# AGENTS.md — omega-mvc/framework

The Omega MVC engine (namespace `Omega\`, PSR-4 `src/Omega/`). This is a standalone Composer
package with its own git repo, `.github/workflows`, and test suite; the `omega-mvc/omega` starter
app consumes it from `vendor/omega-mvc/framework/`. It is **not** Laravel. It depends on
`omega-mvc/gettext` and `omega-mvc/serializable-closure` (also under `vendor/`), which each have
their own `AGENTS.md`.

## Commands

Run from this package directory. The `composer` scripts are the source of truth; the starter app's
OpenCode config denies `composer*`, so call the underlying binaries directly when blocked.

```bash
composer test            # vendor/bin/pest    (Pest 5, not PHPUnit)
composer type-coverage   # vendor/bin/pest --type-coverage
composer phpstan         # vendor/bin/phpstan analyze   (level 10)
composer lint            # vendor/bin/phpcs src/ tests/
composer fix             # phpcbf
composer check           # lint + test   (does NOT run phpstan)
composer ci              # fix + check
```

- Single test / filter: `vendor/bin/pest tests/Unit/Collection/CollectionTest.php` or `--filter=<name>`.
  Unit tests are tagged `--group=unit` in `pest.php`.
- Unlike gettext/serializable-closure, these scripts do **not** prefix `XDEBUG_MODE=off`.
- `phpcs`/`phpstan`/coverage write to `cache/`; create it with `mkdir -p cache/phpcs cache/phpstan`
  when invoking tools directly.

## Tests

- **Pest 5** (`pest.php`, `phpunit.xml.dist`). `pest.php` bootstraps `tests/bootstrap.php`, tags the
  `Tests\` namespace with `group('unit')`, and configures coverage over `src` with a **minimum of 80**.
- Namespace `Tests\` maps to **both** `tests/Unit/` and `tests/` (see `autoload-dev`). Layout:
  `tests/Unit/<Area>/*` and `tests/Feature/**`.
- `phpunit.xml.dist`: `APP_ENV=testing`, `OMEGA_TEST_MODE=light`, `pathCoverage="true"`, HTML report to
  `cache/coverage-report`.
- `tests/Unit/*/fixtures/` is excluded from both phpstan and phpcs — do not lint or analyse fixtures.
- Two known, **intentional** warnings: `tests/Unit/Filesystem/Util/SizeTest` and `ChecksumTest` install a
  temporary error handler so the expected `E_WARNING` from a bad path passed to `filesize()`/`md5_file()`
  does not fail the run. Do not "fix" them.

## Static analysis / lint

- PHPStan level 10 via `phpstan.neon.dist`. It includes `pest-plugin-phpstan` plus two custom services
  (`Omega\PHPStan\Type\ReflectionFunctionAbstractReturnTypeExtension`,
  `Omega\PHPStan\Type\ValidatorMagicPropertiesClassReflectionExtension`), bootstraps `vendor/autoload.php`,
  and excludes `tests/Unit/*/fixtures/*` and `src/Omega/Console/stubs`.
- PHPCS: PSR-12, `severity=10`, line limit 120, paths `src` + `tests`, cache `cache/phpcs/phpcs.json`,
  excludes `tests/Unit/[^/]+/fixtures/`.

## Conventions

- Namespace `Omega\` → `src/Omega/<Area>/`. Some areas ship global helper functions through the composer
  `files` autoload map (`helper.php` in Application, Collection, Environment, Http, Security, Text, Time,
  Validator, View); register any new helper file in `composer.json` as well.
- The starter app subclasses framework extension points such as `Omega\Http\Http`,
  `Omega\Console\ConsoleApplication`, and `Omega\Testing\TestCase` (`app/Kernel/*`), so keep those stable.
- Changes made here belong to this package's git repo: commit them here, or they are lost on
  `composer update` (the starter app does not track `vendor/`).
- CI: `.github/workflows/{tests,coding-standard,static-analysis}.yml`.
