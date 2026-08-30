# Omega Framework Agent Guidance

## Developer Commands
- **Lint**: `composer run lint` (runs `phpcs` with PSR12 on src/ and tests/)
- **Fix Style**: `composer run fix` (runs `phpcbf` with PSR12 on src/ and tests/)
- **Test**: `composer run test` (runs lint then phpunit)
- **Run Tests Only**: `composer run test:phpunit` (requires APP_ENV=testing and OMEGA_TEST_MODE=normal, set in phpunit.xml.dist)
- **Static Analysis**: `vendor/bin/phpstan analyze` (configured at level 10 in phpstan.neon.dist)
- **CI Pipeline**: `composer run ci` (fix -> test)

## Architecture & Conventions
- **Namespace**: `Omega\` mapped to `src/Omega/`
- **Helper Files**: 7 global helpers auto-loaded via composer.json (Application, Collection, Environment, Http, Text, Time, Validator, View)
- **PHP Version**: Requires PHP 8.4+ with extensions: iconv, mbstring, openssl, pcntl, pdo, posix, readline, simplexml
- **Structure**: 28 specialized components under src/Omega/ (e.g., Database, Http, Collection, Validator, Router, Console, Cache, Config)
- **Entry Points**: Application bootstrapped via AbstractApplication; Console apps via Console class
- **Configuration**: Flat config keys preferred (see note.txt [A2] for providers migration)
- **Cache Directories**: `cache/phpcs/`, `cache/phpstan/`, `cache/phpunit/`, `cache/coverage-report/` (gitignored)

## Verification Workflow
1. `composer run fix` (to align with PSR12)
2. `composer run lint`
3. `vendor/bin/phpstan analyze`
4. `composer run test:phpunit` (with testing environment)

## Testing Notes
- Tests require: `APP_ENV=testing` and `OMEGA_TEST_MODE=normal` (set in phpunit.xml.dist)
- Test bootstrap: `tests/bootstrap.php`
- Coverage report generated to `cache/coverage-report/`
- Testdox output enabled for readable test specifications
- Fixtures in `tests/Tests/fixtures/` and `tests/Tests/Support/fixtures/` (excluded from phpcs/phpstan)

## Important Caveats
- Helper function return types changed in recent refactor (see note.txt): 
  - `collection_immutable()` now returns `CollectionImmutable` (was `Collection`)
  - `chunk()`, `assocBy()`, `flatten()` return new instances (was mutating)
  - `sum()`/`avg()` return `int|float` (was `int`)
- PHPStan level 10 enforced; examine note.txt for deliberate type changes
- Environment variables must be set for testing (handled automatically by phpunit.xml.dist)
- `note.txt` is gitignored (contains PHPStan/type change audit log)
