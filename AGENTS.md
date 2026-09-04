# AGENTS.md — Omega MVC Framework

PHP 8.4+ MVC framework library (`Omega\` namespace). Not an application — this is the framework package consumed via Composer.

## Commands

```bash
composer run lint          # phpcs (PSR-12 + 120-col limit, src/ + tests/)
composer run fix           # phpcbf auto-fix
composer run test:phpunit  # phpunit tests/ (no coverage)
composer run test          # lint + phpunit
composer run check         # alias for test
composer run ci            # fix + test (what CI runs)

# Do NOT run PHPStan (phpstan.neon.dist exists, level 10, but is not part of the workflow)
```

Run `lint` before `test`. Fix lint errors with `composer run fix` first.

## Code Style

- **PSR-12** with 120-char line limit (comments included, no hard absolute limit)
- camelCase method names (PSR-1 method rule excluded in phpcs.xml.dist)
- 4-space indent, UTF-8, LF line endings
- Fixtures directories excluded from linting: `tests/Tests/Support/fixtures`, `tests/Tests/fixtures`

## Structure

- `src/Omega/` — 28 subpackages (Application, Cache, Collection, Container, Database, Http, Router, View, etc.)
- `tests/Tests/` — mirrors src structure, 342 test files
- Global helper files autoloaded via Composer `files`: `Application/helper.php`, `Collection/helper.php`, `Environment/helper.php`, `Http/helper.php`, `Text/helper.php`, `Time/helper.php`, `Validator/helper.php`, `View/helper.php`
- `cache/` — runtime cache (phpcs, phpstan, phpunit, coverage); gitignored

## Testing

- **Pest 5** on top of PHPUnit. Tests are PHPUnit-style classes with `#[CoversClass]` attributes
- Run tests via `composer run test:phpunit` — no coverage by default (80% minimum only if `--coverage` is passed)
- Test env: `APP_ENV=testing`, `OMEGA_TEST_MODE=light`
- Coverage reports: `cache/coverage-report/`
- No external services required for unit tests
- Archive `PharAdapter` real-write tests would skip when `phar.readonly=1` (the default, PHP_INI_SYSTEM so it
  cannot be overridden at runtime), so write paths were refactored: `PharAdapter` now depends on an injectable
  `PharEngineInterface` (default `NativePharEngine`). Write/delete/rename success+failure paths are tested against
  in-memory fakes (`FakePharEngine`, `FailingPharEngine`, `UnreadablePharEngine`) with no skip, giving
  `PharAdapter` 100% lines/branches/paths in `cache/coverage-report/Archive/`.
- `NativePharEngine` takes `Phar|PharData`: its write/delete paths are covered at 100% by testing against
  `PharData`, which is writable even with `phar.readonly=1`; the fakes cover every branch of `PharAdapter`.
- `Bz2Adapter::rename()` avoids a compound `||` guard so its paths are fully exercised by the test suite
  (100% lines/branches/paths).
- Fixture dirs: `tests/Tests/fixtures/`, `tests/Tests/Support/fixtures/`

## Conventions

- `declare(strict_types=1);` in every PHP file
- GPL-3.0 license headers on all source and test files
- Facades use `AbstractFacade::flushInstance()` at request boundaries
- Container has `setRequestScoped()` / `resetRequestScope()` for persistent-worker support
- Database uses persistent PDO with automatic reconnect-on-lost-connection

## Notes

- No CI pipelines configured (no `.github/workflows/` or equivalent)
- `composer.lock` is gitignored but currently tracked — edit `composer.json` dependencies directly
- `phpstan.neon.dist` exists (level 10) but is NOT part of the workflow — do not run it
- Available tools on dev machine: `rg`, `tig`, `phpdbg`, `python3`
