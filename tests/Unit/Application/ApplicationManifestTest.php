<?php

declare(strict_types=1);

namespace Tests\Application;

use Omega\Application\ApplicationManifest;
use ReflectionProperty;
use Tests\FixturesPathTrait;

use function chmod;
use function dirname;
use function file_exists;
use function file_put_contents;
use function glob;
use function is_dir;
use function json_encode;
use function mkdir;
use function restore_error_handler;
use function rmdir;
use function set_error_handler;
use function str_contains;
use function unlink;
use function var_export;
use function Omega\Application\slash;

covers(ApplicationManifest::class);

uses(FixturesPathTrait::class);

beforeEach(function (): void {
    $this->basePath             = $this->setFixturePath('/fixtures/application-read/');
    $this->applicationCachePath = $this->setFixturePath('/fixtures/application-write/bootstrap/cache/');
    $this->applicationManifest  = $this->setFixturePath('/fixtures/application-write/bootstrap/cache/packages.php');
});

afterEach(function (): void {
    if (file_exists($this->applicationManifest)) {
        @unlink($this->applicationManifest);
    }
});

it('can build', function (): void {
    $applicationManifest = new ApplicationManifest($this->basePath, $this->applicationCachePath, '/package/');
    $applicationManifest->build();

    expect(file_exists($this->applicationManifest))->toBeTrue();
});

it('can get application manifest', function (): void {
    $applicationManifest = new ApplicationManifest($this->basePath, $this->applicationCachePath, '/package/');

    $manifest1 = (fn () => $this->{'getApplicationManifest'}())->call($applicationManifest);
    $manifest2 = (fn () => $this->{'getApplicationManifest'}())->call($applicationManifest);

    $expected = [
        'packages/package1' => [
            'providers' => [
                'Package//Package1//ServiceProvider::class',
            ],
        ],
        'packages/package2' => [
            'providers' => [
                'Package//Package2//ServiceProvider::class',
                'Package//Package2//ServiceProvider2::class',
            ],
        ],
    ];

    expect($manifest1)->toEqual($expected);
    expect($manifest2)->toEqual($expected);
    expect($manifest1)->toBe($manifest2);
});

it('can get config', function (): void {
    $package_manifest = new ApplicationManifest(
        $this->basePath,
        $this->applicationCachePath,
        slash(path: '/package/')
    );
    $config = (fn () => $this->{'config'}('providers'))->call($package_manifest);

    expect($config)->toEqual([
        'Package//Package1//ServiceProvider::class',
        'Package//Package2//ServiceProvider::class',
        'Package//Package2//ServiceProvider2::class',
    ]);
});

it('can get providers', function (): void {
    $package_manifest = new ApplicationManifest(
        $this->basePath,
        $this->applicationCachePath,
        slash(path: '/package/')
    );

    $config = $package_manifest->providers();

    expect($config)->toEqual([
        'Package//Package1//ServiceProvider::class',
        'Package//Package2//ServiceProvider::class',
        'Package//Package2//ServiceProvider2::class',
    ]);
});

it('filters out incomplete packages, string entries and empty values', function (): void {
    file_put_contents(
        $this->applicationCachePath . 'packages.php',
        '<?php return ' . var_export([
            'pkg_not_array' => 'plain-string',
            'pkg_no_key'    => ['name' => 'x'],
            'pkg_string'    => ['providers' => 'SingleProvider'],
            'pkg_array'     => ['providers' => ['A', '', 'B']],
        ], true) . ';'
    );

    $manifest = new ApplicationManifest($this->basePath, $this->applicationCachePath);

    expect($manifest->providers())->toBe(['SingleProvider', 'A', 'B']);
});

it('treats a non-array cached manifest as empty', function (): void {
    file_put_contents(
        $this->applicationCachePath . 'packages.php',
        "<?php return 'not-an-array';"
    );

    $manifest = new ApplicationManifest($this->basePath, $this->applicationCachePath);

    expect($manifest->providers())->toBe([]);
});

it('collects config values across varied package shapes', function (): void {
    file_put_contents(
        $this->applicationCachePath . 'packages.php',
        '<?php return ' . var_export([
            'pkg_object' => (object) ['providers' => ['Kept']],
            'pkg_list'   => ['providers' => ['Keep', 5, '', 'Yes']],
            'pkg_int'    => ['providers' => 123],
            'pkg_empty'  => ['providers' => ''],
        ], true) . ';'
    );

    $manifest = new ApplicationManifest($this->basePath, $this->applicationCachePath);

    expect($manifest->providers())->toBe(['Keep', 'Yes']);
});

it('uses a custom vendor path', function (): void {
    $customPath = '/custom/vendor/';
    $manifest   = new ApplicationManifest('/base', '/cache', $customPath);

    $reflection = new ReflectionProperty(ApplicationManifest::class, 'vendorPath');
    $reflection->setAccessible(true);

    expect($reflection->getValue($manifest))->toBe(slash($customPath));
});

it('uses the default vendor path', function (): void {
    $manifest = new ApplicationManifest('/base', '/cache');

    $reflection = new ReflectionProperty(ApplicationManifest::class, 'vendorPath');
    $reflection->setAccessible(true);

    expect($reflection->getValue($manifest))->toBe(slash('/vendor/composer/'));
});

it('builds when the installed.json is not a packages array', function (): void {
    $tempCachePath = $this->setFixturePath('/fixtures/application-write/bootstrap/cache-malformed/');
    $tempBase      = $this->setFixturePath('/fixtures/application-write/malformed-base/');

    if (!is_dir($tempCachePath)) {
        mkdir($tempCachePath, 0777, true);
    }
    if (!is_dir($tempBase . '/package/composer/')) {
        mkdir($tempBase . '/package/composer/', 0777, true);
    }
    file_put_contents($tempBase . '/package/composer/installed.json', 'not-valid-json');

    $applicationManifest = new ApplicationManifest($tempBase, $tempCachePath, '/package/composer/');
    $applicationManifest->build();

    expect(file_exists($tempCachePath . 'packages.php'))->toBeTrue();

    @unlink($tempCachePath . 'packages.php');
    @unlink($tempBase . '/package/composer/installed.json');
    @rmdir($tempBase . '/package/composer');
    @rmdir($tempBase . '/package');
    @rmdir($tempBase);
    @rmdir($tempCachePath);
});

it('builds when installed.json decodes without a packages array', function (): void {
    $tempCachePath = $this->setFixturePath('/fixtures/application-write/bootstrap/cache-malformed-2/');
    $tempBase      = $this->setFixturePath('/fixtures/application-write/malformed-base-2/');

    if (!is_dir($tempCachePath)) {
        mkdir($tempCachePath, 0777, true);
    }
    if (!is_dir($tempBase . '/package/composer/')) {
        mkdir($tempBase . '/package/composer/', 0777, true);
    }
    file_put_contents($tempBase . '/package/composer/installed.json', json_encode(['foo' => 'bar']));

    $applicationManifest = new ApplicationManifest($tempBase, $tempCachePath, '/package/composer/');
    $applicationManifest->build();

    expect(file_exists($tempCachePath . 'packages.php'))->toBeTrue();

    @unlink($tempCachePath . 'packages.php');
    @unlink($tempBase . '/package/composer/installed.json');
    @rmdir($tempBase . '/package/composer');
    @rmdir($tempBase . '/package');
    @rmdir($tempBase);
    @rmdir($tempCachePath);
});

it('builds while filtering out packages failing each validation guard', function (): void {
    $tempCachePath = $this->setFixturePath('/fixtures/application-write/bootstrap/cache-filters/');
    $tempBase      = $this->setFixturePath('/fixtures/application-write/filters-base/');

    if (!is_dir($tempCachePath)) {
        mkdir($tempCachePath, 0777, true);
    }
    if (!is_dir($tempBase . '/package/composer/')) {
        mkdir($tempBase . '/package/composer/', 0777, true);
    }

    file_put_contents(
        $tempBase . '/package/composer/installed.json',
        json_encode(['packages' => [
            'not-an-array',
            ['version' => '1.0'],
            ['name' => 123],
            ['name' => 'ok', 'version' => '1.0'],
            ['name' => 'ok2', 'extra' => 'not-array'],
            ['name' => 'ok3', 'extra' => ['a' => 1]],
            ['name' => 'valid', 'extra' => ['omega-mvc' => ['providers' => ['X']]]],
        ]])
    );

    $applicationManifest = new ApplicationManifest($tempBase, $tempCachePath, '/package/composer/');
    $applicationManifest->build();

    $manifest = require $tempCachePath . 'packages.php';

    expect($manifest)->toBe(['valid' => ['providers' => ['X']]]);
    expect(file_exists($tempCachePath . 'packages.php'))->toBeTrue();

    @unlink($tempCachePath . 'packages.php');
    @unlink($tempBase . '/package/composer/installed.json');
    @rmdir($tempBase . '/package/composer');
    @rmdir($tempBase . '/package');
    @rmdir($tempBase);
    @rmdir($tempCachePath);
});

it('builds when the decoded packages key is not an array', function (): void {
    $tempCachePath = $this->setFixturePath('/fixtures/application-write/bootstrap/cache-not-array/');
    $tempBase      = $this->setFixturePath('/fixtures/application-write/not-array-base/');

    if (!is_dir($tempCachePath)) {
        mkdir($tempCachePath, 0777, true);
    }
    if (!is_dir($tempBase . '/package/composer/')) {
        mkdir($tempBase . '/package/composer/', 0777, true);
    }
    file_put_contents(
        $tempBase . '/package/composer/installed.json',
        json_encode(['packages' => 'not-an-array'])
    );

    $applicationManifest = new ApplicationManifest($tempBase, $tempCachePath, '/package/composer/');
    $applicationManifest->build();

    expect(file_exists($tempCachePath . 'packages.php'))->toBeTrue();

    @unlink($tempCachePath . 'packages.php');
    @unlink($tempBase . '/package/composer/installed.json');
    @rmdir($tempBase . '/package/composer');
    @rmdir($tempBase . '/package');
    @rmdir($tempBase);
    @rmdir($tempCachePath);
});

it('builds when the installed.json file cannot be read', function (): void {
    $tempCachePath = $this->setFixturePath('/fixtures/application-write/bootstrap/cache-unreadable/');
    $tempBase      = $this->setFixturePath('/fixtures/application-write/unreadable-base/');

    if (!is_dir($tempCachePath)) {
        mkdir($tempCachePath, 0777, true);
    }
    if (!is_dir($tempBase . '/package/composer/')) {
        mkdir($tempBase . '/package/composer/', 0777, true);
    }
    file_put_contents($tempBase . '/package/composer/installed.json', '{"packages":[]}');
    chmod($tempBase . '/package/composer/installed.json', 0000);

    $applicationManifest = new ApplicationManifest($tempBase, $tempCachePath, '/package/composer/');

    set_error_handler(static function (int $severity, string $message): bool {
        return str_contains($message, 'file_get_contents');
    });

    try {
        $applicationManifest->build();
    } finally {
        restore_error_handler();
    }

    expect(file_exists($tempCachePath . 'packages.php'))->toBeTrue();

    @chmod($tempBase . '/package/composer/installed.json', 0644);
    @unlink($tempBase . '/package/composer/installed.json');
    @rmdir($tempBase . '/package/composer');
    @rmdir($tempBase . '/package');
    @rmdir($tempBase);
    @unlink($tempCachePath . 'packages.php');
    @rmdir($tempCachePath);
});

it('gets an empty application manifest when the cache file is missing', function (): void {
    $tempCachePath = $this->setFixturePath('/fixtures/application-write/bootstrap/cache-missing/');

    if (!is_dir($tempCachePath)) {
        mkdir($tempCachePath, 0777, true);
    }

    $applicationManifest = new ApplicationManifest($this->basePath, $tempCachePath);

    $manifest = (fn () => $this->{'getApplicationManifest'}())->call($applicationManifest);

    expect($manifest)->toBeArray();
    expect(file_exists($tempCachePath . 'packages.php'))->toBeTrue();

    @unlink($tempCachePath . 'packages.php');
});

it('gets the application manifest when the cache file exists', function (): void {
    if (!is_dir($this->applicationCachePath)) {
        mkdir($this->applicationCachePath, 0777, true);
    }
    file_put_contents($this->applicationCachePath . 'packages.php', "<?php return ['test' => 'data'];");

    $applicationManifest = new ApplicationManifest($this->basePath, $this->applicationCachePath);

    $ref = new ReflectionProperty(ApplicationManifest::class, 'applicationManifest');
    $ref->setAccessible(true);
    $ref->setValue($applicationManifest, null);

    $manifest = (fn () => $this->{'getApplicationManifest'}())->call($applicationManifest);

    expect($manifest)->toEqual(['test' => 'data']);
});

it('collects only valid external packages in findExternalProvider', function (): void {
    $tempBase      = $this->setFixturePath('/fixtures/application-write/external-valid/');
    $tempCachePath = $this->setFixturePath('/fixtures/application-write/external-valid-cache/');

    writeExternalComposer(
        'ext/valid',
        ['omega-mvc' => ['providers' => ['External::class']]],
        $tempBase,
        $tempCachePath
    );

    $applicationManifest = new ApplicationManifest($tempBase, $tempCachePath, '/package/composer/');
    $manifest            = $applicationManifest->build();

    expect($manifest)->toBe(['ext/valid' => ['providers' => ['External::class']]]);

    cleanExternalTree($tempBase, $tempCachePath);
});

it('continues past invalid packages and collects valid ones in findExternalProvider', function (): void {
    $tempBase      = $this->setFixturePath('/fixtures/application-write/external-mixed/');
    $tempCachePath = $this->setFixturePath('/fixtures/application-write/external-mixed-cache/');

    if (!is_dir($tempCachePath)) {
        mkdir($tempCachePath, 0777, true);
    }

    $invalid = $tempBase . '/vendor/omega-mvc/invalid/composer.json';
    mkdir(dirname($invalid), 0777, true);
    file_put_contents($invalid, 'not-valid-json');

    writeExternalComposer(
        'ext/mixed',
        ['omega-mvc' => ['providers' => ['Mixed::class']]],
        $tempBase,
        $tempCachePath
    );

    $applicationManifest = new ApplicationManifest($tempBase, $tempCachePath, '/package/composer/');
    $manifest            = $applicationManifest->build();

    expect($manifest)->toBe(['ext/mixed' => ['providers' => ['Mixed::class']]]);

    cleanExternalTree($tempBase, $tempCachePath);
});

it('ignores a composer.json that cannot be read in findExternalProvider', function (): void {
    $tempBase      = $this->setFixturePath('/fixtures/application-write/external-unreadable/');
    $tempCachePath = $this->setFixturePath('/fixtures/application-write/external-unreadable-cache/');

    $file = $tempBase . '/vendor/omega-mvc/unreadable/composer.json';

    if (!is_dir($tempCachePath)) {
        mkdir($tempCachePath, 0777, true);
    }
    mkdir(dirname($file), 0777, true);
    file_put_contents($file, '{"name":"ext/unreadable","extra":{"omega-mvc":{"providers":["X"]}}}');
    chmod($file, 0000);

    set_error_handler(static function (int $severity, string $message): bool {
        return str_contains($message, 'file_get_contents');
    });

    try {
        $applicationManifest = new ApplicationManifest($tempBase, $tempCachePath, '/package/composer/');
        $manifest            = $applicationManifest->build();
    } finally {
        restore_error_handler();
    }

    expect($manifest)->toBe([]);

    @chmod($file, 0644);
    cleanExternalTree($tempBase, $tempCachePath);
});

it('ignores invalid JSON composer files in findExternalProvider', function (): void {
    $tempBase      = $this->setFixturePath('/fixtures/application-write/external-invalid-json/');
    $tempCachePath = $this->setFixturePath('/fixtures/application-write/external-invalid-json-cache/');

    $file = $tempBase . '/vendor/omega-mvc/invalid/composer.json';

    if (!is_dir($tempCachePath)) {
        mkdir($tempCachePath, 0777, true);
    }
    mkdir(dirname($file), 0777, true);
    file_put_contents($file, 'not-valid-json');

    $applicationManifest = new ApplicationManifest($tempBase, $tempCachePath, '/package/composer/');
    $manifest            = $applicationManifest->build();

    expect($manifest)->toBe([]);

    cleanExternalTree($tempBase, $tempCachePath);
});

it('ignores packages without a string name in findExternalProvider', function (): void {
    $tempBase      = $this->setFixturePath('/fixtures/application-write/external-no-name/');
    $tempCachePath = $this->setFixturePath('/fixtures/application-write/external-no-name-cache/');

    writeExternalComposer(
        null,
        ['omega-mvc' => ['providers' => ['X']]],
        $tempBase,
        $tempCachePath,
        'noname'
    );

    $applicationManifest = new ApplicationManifest($tempBase, $tempCachePath, '/package/composer/');
    $manifest            = $applicationManifest->build();

    expect($manifest)->toBe([]);

    cleanExternalTree($tempBase, $tempCachePath);
});

it('ignores packages without an extra array in findExternalProvider', function (): void {
    $tempBase      = $this->setFixturePath('/fixtures/application-write/external-no-extra/');
    $tempCachePath = $this->setFixturePath('/fixtures/application-write/external-no-extra-cache/');

    writeExternalComposer('ext/noextra', null, $tempBase, $tempCachePath);

    $applicationManifest = new ApplicationManifest($tempBase, $tempCachePath, '/package/composer/');
    $manifest            = $applicationManifest->build();

    expect($manifest)->toBe([]);

    cleanExternalTree($tempBase, $tempCachePath);
});

it('ignores packages lacking the omega-mvc extra key in findExternalProvider', function (): void {
    $tempBase      = $this->setFixturePath('/fixtures/application-write/external-no-omega/');
    $tempCachePath = $this->setFixturePath('/fixtures/application-write/external-no-omega-cache/');

    writeExternalComposer('ext/noomega', ['other' => 1], $tempBase, $tempCachePath);

    $applicationManifest = new ApplicationManifest($tempBase, $tempCachePath, '/package/composer/');
    $manifest            = $applicationManifest->build();

    expect($manifest)->toBe([]);

    cleanExternalTree($tempBase, $tempCachePath);
});

function writeExternalComposer(
    ?string $name,
    mixed $extra,
    string $tempBase,
    string $tempCachePath,
    string $dir = 'pkg'
): void {
    if (!is_dir($tempCachePath)) {
        mkdir($tempCachePath, 0777, true);
    }

    $file = $tempBase . '/vendor/omega-mvc/' . $dir . '/composer.json';
    mkdir(dirname($file), 0777, true);

    $composer = [];
    if ($name !== null) {
        $composer['name'] = $name;
    }
    if ($extra !== null) {
        $composer['extra'] = $extra;
    }

    file_put_contents($file, json_encode($composer));
}

function cleanExternalTree(string $tempBase, string $tempCachePath): void
{
    $vendor = $tempBase . '/vendor';

    if (is_dir($vendor)) {
        foreach (glob($vendor . '/omega-mvc/*') ?: [] as $pkg) {
            foreach (glob($pkg . '/*') ?: [] as $f) {
                @unlink($f);
            }
            @rmdir($pkg);
        }
        @rmdir($vendor . '/omega-mvc');
        @rmdir($vendor);
    }

    @rmdir($tempBase);
    @unlink($tempCachePath . 'packages.php');
    @rmdir($tempCachePath);
}