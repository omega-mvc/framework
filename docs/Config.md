# Omega MVC — Config Package Manual

The `Omega\Config` package provides the application configuration repository: a
dot-notation key/value store built over plain PHP config files, with array/JSON/XML
sources, merge strategies, and a bootstrap step that loads the application config at
startup.

## Config files

Config lives in PHP files that `return` an associative array. The
`ConfigBootstrapper` scans the config directory (`path.config`), requires every
`*.php` file, and merges them with `array_replace_recursive`. You can split config
into multiple files (`app.php`, `database.php`, `cache.php`...).

```php
// config/app.php
return [
    'name'    => 'Omega',
    'debug'   => false,
    'database' => [
        'default' => 'sqlite',
        'connections' => [
            'sqlite' => ['driver' => 'sqlite', 'database' => ':memory:'],
        ],
    ],
];
```

Values are read with dot notation: `config('database.default')` resolves to
`'sqlite'`.

## Facade

```php
use Omega\Config\Facade\Config;

Config::get('app.name', 'default');       // value or default
Config::has('app.debug');                 // bool
Config::set('app.debug', true);           // set (also via array-access: $config['k'] = v)
Config::push('cache.paths', '/tmp');      // append to an array key
Config::getAll();                         // the whole config array
```

The `Config` facade resolves the container binding `ConfigRepository::class`.

## Repository API

```php
use Omega\Config\ConfigRepository;

$config = new ConfigRepository(['key' => 'value']);

$config->get('nested.key', 'fallback');   // dot-notation get
$config->has('nested.key');
$config->set('nested.key', 'value');      // auto-creates intermediate arrays
$config->remove('nested.key');            // unset leaf
$config->push('timeline', 'entry');       // append
$config->clear();                         // reset to []
$config->getAll();
```

`ConfigRepository` implements `ArrayAccess` (so `$config['db.port']` works),
`Countable`, and `IteratorAggregate`.

## Merging repositories

```php
use Omega\Config\ConfigRepository;
use Omega\Config\MergeStrategy;

$base  = new ConfigRepository(['indexed' => [1, 2, 3]]);
$extra = new ConfigRepository(['indexed' => [3, 4, 5]]);

$base->merge($extra);                                   // replace indexed arrays (default)
$base->merge($extra, null, MergeStrategy::MERGE_INDEXED);   // [1,2,3,4,5]
$base->merge($extra, null, MergeStrategy::MERGE_ADD_NEW);   // keep existing keys

// Merge into a specific sub-key:
$base->merge($extra, 'nested');
```

Strategies:

- `REPLACE_INDEXED` (default) — indexed arrays are replaced wholesale; associative
  sub-arrays merge recursively.
- `MERGE_INDEXED` — indexed arrays merge and deduplicate with `array_unique`.
- `MERGE_ADD_NEW` — only keys that do not exist in the target are added.

## Sources and ConfigBuilder

Sources describe where config data comes from; each implements `SourceInterface`
with a single `fetch(): array`:

```php
use Omega\Config\ConfigBuilder;
use Omega\Config\Source\ArrayConfig;
use Omega\Config\Source\JsonConfig;
use Omega\Config\Source\XmlConfig;

// In-memory array, JSON file, XML file:
new ArrayConfig(['key' => 'value']);
new JsonConfig($jsonFilePath);
new XmlConfig($xmlFilePath);
```

Load several sources into one repository, optionally grouped under a section and
ordered by priority (higher priority wins):

```php
$builder = new ConfigBuilder();

$config = $builder
    ->addConfiguration(new ArrayConfig(['key' => 'value']))
    ->addConfiguration(new JsonConfig('defaults.json'))
    ->addConfiguration(new ArrayConfig(['private' => 'x']), 'secrets', 10)
    ->build();
```

Call `build(MergeStrategy::MERGE_INDEXED)` to override the default merge strategy.

## Bootstrapping

In a framework application the `ConfigBootstrapper::bootstrap(ApplicationInterface)`
loads all config files (or a cached `config.php` from the application cache path),
builds the `ConfigRepository`, pushes it into the application via
`$app->loadConfig(...)`, and sets the default timezone from `env('APP_TIMEZONE')`
(`'UTC'` fallback). Results are cached statically so persistent workers do not
re-read config on every request.

## Exceptions

All implement `Omega\Config\Exceptions\ConfigExceptionInterface`:

| Exception | When thrown |
| --------- | ----------- |
| `FileReadException` | a source file cannot be read |
| `MalformedJsonException` | a JSON source cannot be decoded |
| `MalformedXmlException` | an XML source cannot be parsed |
| `InvalidArrayConfigException` | an `ArrayConfig` is empty or not associative |

`ConfigBootstrapper` additionally throws `RuntimeException` if a config or cache file
does not return an array.

## Reference

- `ConfigRepository.php`, `AbstractConfigRepository.php`, `ConfigRepositoryInterface.php`
- `ConfigTrait.php`, `MergeStrategy.php`, `ConfigBuilder.php`
- `Source/` — `SourceInterface`, `AbstractSource`, `ArrayConfig`, `JsonConfig`, `XmlConfig`
- `Bootstrapper/ConfigBootstrapper.php`
- Facade: `Facade/Config.php` (accessor `ConfigRepository::class`)
- Exceptions: `Exceptions/`
- License: GPL-3.0+