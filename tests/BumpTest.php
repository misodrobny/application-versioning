<?php

use DrobnyDev\ApplicationVersioning\ApplicationVersioning;
use DrobnyDev\ApplicationVersioning\Exceptions\InvalidArgumentException;
use DrobnyDev\ApplicationVersioning\Exceptions\VersionFileException;
use Illuminate\Cache\TaggableStore;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Sleep;

beforeEach(function () {
    $this->versionFile = sys_get_temp_dir().'/av-bump-'.bin2hex(random_bytes(4)).'.yaml';

    config([
        'application-versioning.version_file_path' => $this->versionFile,
        'application-versioning.disk' => null,
        'application-versioning.git_path' => sys_get_temp_dir().'/av-no-git',
    ]);
    app()->forgetInstance(ApplicationVersioning::class);

    $this->travelTo(Carbon::create(2026, 10, 20));
});

afterEach(function () {
    @unlink($this->versionFile);
});

function writeVersion(string $file, int $major, int $minor, int $patch): void
{
    file_put_contents($file, "version:\n    current: { major: {$major}, minor: {$minor}, patch: {$patch}, format: \$major.\$minor.\$patch }\n");
}

function currentVersion(): string
{
    $current = app(ApplicationVersioning::class)->getYamlContent()['version']['current'];

    return "{$current['major']}.{$current['minor']}.{$current['patch']}";
}

it('bumps a calendar version', function (array $from, ?string $part, string $expected) {
    writeVersion($this->versionFile, ...$from);

    app(ApplicationVersioning::class)->bump($part);

    expect(currentVersion())->toBe($expected);
})->with([
    'same month' => [[2026, 10, 3], null, '2026.10.4'],
    'new month' => [[2026, 9, 7], null, '2026.10.1'],
    'part ignored' => [[2026, 10, 3], 'major', '2026.10.4'],
]);

it('moves a calendar version into the new year', function () {
    $this->travelTo(Carbon::create(2027, 1, 5));
    writeVersion($this->versionFile, 2026, 12, 9);

    app(ApplicationVersioning::class)->bump();

    expect(currentVersion())->toBe('2027.1.1');
});

it('bumps a SemVer part and resets the lower parts', function (string $part, string $expected) {
    writeVersion($this->versionFile, 1, 4, 7);

    app(ApplicationVersioning::class)->bump($part);

    expect(currentVersion())->toBe($expected);
})->with([
    'major' => ['major', '2.0.0'],
    'minor' => ['minor', '1.5.0'],
    'patch' => ['patch', '1.4.8'],
]);

it('needs a part for a SemVer version', function () {
    writeVersion($this->versionFile, 1, 4, 7);

    expect(fn () => app(ApplicationVersioning::class)->bump())->toThrow(InvalidArgumentException::class);
});

it('rejects an unknown part', function () {
    writeVersion($this->versionFile, 1, 4, 7);

    expect(fn () => app(ApplicationVersioning::class)->bump('build'))
        ->toThrow(InvalidArgumentException::class, 'Invalid version type');
});

it('delegates the increase methods to bump', function () {
    writeVersion($this->versionFile, 1, 4, 7);

    app(ApplicationVersioning::class)->increaseMinor();
    expect(currentVersion())->toBe('1.5.0');

    app(ApplicationVersioning::class)->increaseVersion('patch');
    expect(currentVersion())->toBe('1.5.1');
});

it('keeps other keys and the flow-map layout', function () {
    file_put_contents($this->versionFile, implode("\n", [
        'version:',
        '    current: { major: 1, minor: 4, patch: 7, format: $major.$minor.$patch }',
        '    previous: { major: 1, minor: 3, patch: 0 }',
        'meta:',
        '    owner: x',
        '',
    ]));

    app(ApplicationVersioning::class)->bump('patch');

    $content = app(ApplicationVersioning::class)->getYamlContent();

    expect($content['version']['previous'])->toBe(['major' => 1, 'minor' => 3, 'patch' => 0])
        ->and($content['meta'])->toBe(['owner' => 'x'])
        ->and(file_get_contents($this->versionFile))->toMatch('/^version:\n    current: \{ major: 1, minor: 4, patch: 8, format:/m');
});

it('throws VersionFileException when bumping a missing file', function () {
    expect(fn () => app(ApplicationVersioning::class)->bump('patch'))->toThrow(VersionFileException::class);
});

it('does not write while another bump holds the lock', function () {
    writeVersion($this->versionFile, 1, 4, 7);
    $before = file_get_contents($this->versionFile);
    Sleep::fake(syncWithCarbon: true);

    Cache::lock('application-versioning:bump:'.md5($this->versionFile), 10)->get();

    expect(fn () => app(ApplicationVersioning::class)->bump('patch'))->toThrow(LockTimeoutException::class)
        ->and(file_get_contents($this->versionFile))->toBe($before);
});

it('bumps without a lock when the cache store has none', function () {
    Cache::extend('nolocks', fn () => Cache::repository(new class extends TaggableStore implements Store
    {
        /** @var array<string, mixed> */
        private array $items = [];

        public function get($key)
        {
            return $this->items[$key] ?? null;
        }

        public function put($key, $value, $seconds)
        {
            $this->items[$key] = $value;

            return true;
        }

        public function increment($key, $value = 1)
        {
            return $this->items[$key] = (int) ($this->items[$key] ?? 0) + $value;
        }

        public function decrement($key, $value = 1)
        {
            return $this->increment($key, -$value);
        }

        public function forever($key, $value)
        {
            return $this->put($key, $value, 0);
        }

        public function touch($key, $seconds)
        {
            return array_key_exists($key, $this->items);
        }

        public function forget($key)
        {
            unset($this->items[$key]);

            return true;
        }

        public function flush()
        {
            $this->items = [];

            return true;
        }

        public function getPrefix()
        {
            return '';
        }

        public function many(array $keys)
        {
            return array_map(fn ($key) => $this->get($key), array_combine($keys, $keys));
        }

        public function putMany(array $values, $seconds)
        {
            foreach ($values as $key => $value) {
                $this->put($key, $value, $seconds);
            }

            return true;
        }
    }));
    config(['cache.stores.nolocks' => ['driver' => 'nolocks'], 'cache.default' => 'nolocks']);
    writeVersion($this->versionFile, 1, 4, 7);

    app(ApplicationVersioning::class)->bump('patch');

    expect(currentVersion())->toBe('1.4.8');
});
