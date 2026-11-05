<?php

use DrobnyDev\ApplicationVersioning\ApplicationVersioning;
use DrobnyDev\ApplicationVersioning\Exceptions\VersionFileException;
use DrobnyDev\ApplicationVersioning\Facades\ApplicationVersioning as ApplicationVersioningFacade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->versionFile = sys_get_temp_dir().'/av-version-'.bin2hex(random_bytes(4)).'.yaml';
    file_put_contents($this->versionFile, "version:\n    current: { major: 2026, minor: 10, patch: 3, format: \$major.\$minor.\$patch - \$git_hash }\n");

    config([
        'application-versioning.version_file_path' => $this->versionFile,
        'application-versioning.disk' => null,
        'application-versioning.git_path' => sys_get_temp_dir().'/av-no-git',
    ]);
    app()->forgetInstance(ApplicationVersioning::class);
});

afterEach(function () {
    @unlink($this->versionFile);
});

it('is bound as a singleton that the facade and the static helper share', function () {
    $instance = app(ApplicationVersioning::class);

    expect(app(ApplicationVersioning::class))->toBe($instance)
        ->and(ApplicationVersioningFacade::getFacadeRoot())->toBe($instance);
});

it('formats the version from the local file', function () {
    expect(ApplicationVersioning::getFormatedVersion())->toBe('2026.10.3 - ');
});

it('increases a part and writes it back', function () {
    app(ApplicationVersioning::class)->increasePatch();

    expect(app(ApplicationVersioning::class)->getYamlContent()['version']['current']['patch'])->toBe(4);
});

it('reads and writes version.yaml through a configured disk', function () {
    Storage::fake('versions');
    Storage::disk('versions')->put('app/version.yaml', "version:\n    current: { major: 1, minor: 2, patch: 3, format: \$major.\$minor.\$patch }\n");
    config(['application-versioning.disk' => 'versions', 'application-versioning.disk_path' => 'app/version.yaml']);
    app()->forgetInstance(ApplicationVersioning::class);

    $versioning = app(ApplicationVersioning::class);
    $versioning->increaseMinor();

    expect($versioning->exists())->toBeTrue()
        ->and(ApplicationVersioning::getFormatedVersion())->toBe('1.3.3')
        ->and(Storage::disk('versions')->get('app/version.yaml'))->toContain('minor: 3');
});

it('creates a calendar-numbered version.yaml that parses back', function () {
    unlink($this->versionFile);
    $versioning = app(ApplicationVersioning::class);

    expect($versioning->exists())->toBeFalse();

    $versioning->create();

    expect($versioning->exists())->toBeTrue()
        ->and(file_get_contents($this->versionFile))->not->toContain("\t")
        ->and($versioning->getYamlContent()['version']['current'])->toBe([
            'major' => (int) date('Y'),
            'minor' => (int) date('n'),
            'patch' => 1,
            'format' => ApplicationVersioning::DEFAULT_FORMAT,
        ]);
});

it('returns the configured fallback and logs a warning when version.yaml is missing', function () {
    unlink($this->versionFile);
    config(['application-versioning.missing_version' => 'dev']);
    Log::spy();

    expect(app(ApplicationVersioning::class)->getFormattedVersion())->toBe('dev');

    Log::shouldHaveReceived('warning')->once()->withArgs(fn (string $message) => str_contains($message, $this->versionFile));
});

it('returns the fallback for invalid content', function (string $contents) {
    file_put_contents($this->versionFile, $contents);

    expect(app(ApplicationVersioning::class)->getFormattedVersion())->toBe('');
})->with([
    'not yaml' => ['not yaml: ['],
    'no patch' => ["version:\n    current: { major: 1, minor: 2, format: x }\n"],
    'quoted patch' => ["version:\n    current: { major: 1, minor: 2, patch: '3', format: x }\n"],
]);

it('throws VersionFileException naming the missing key', function () {
    file_put_contents($this->versionFile, "version:\n    current: { major: 1, minor: 2, patch: '3', format: x }\n");

    expect(fn () => app(ApplicationVersioning::class)->getYamlContent())
        ->toThrow(VersionFileException::class, 'patch');
});

it('names disk and path when the file is missing on a disk', function () {
    Storage::fake('versions');
    config(['application-versioning.disk' => 'versions', 'application-versioning.disk_path' => 'app/version.yaml']);
    app()->forgetInstance(ApplicationVersioning::class);

    try {
        app(ApplicationVersioning::class)->getYamlContent();
        $this->fail('VersionFileException was not thrown');
    } catch (VersionFileException $e) {
        expect($e->getMessage())->toContain('versions')->toContain('app/version.yaml');
    }
});

it('detects the calendar scheme from major', function () {
    expect(app(ApplicationVersioning::class)->isCalendar())->toBeTrue();

    file_put_contents($this->versionFile, "version:\n    current: { major: 1, minor: 2, patch: 3, format: x }\n");

    expect(app(ApplicationVersioning::class)->isCalendar())->toBeFalse();
});

it('keeps the deprecated static getFormatedVersion working', function () {
    expect(ApplicationVersioning::getFormatedVersion())->toBe('2026.10.3 - ')
        ->and(ApplicationVersioningFacade::getFormattedVersion())->toBe('2026.10.3 - ');
});
