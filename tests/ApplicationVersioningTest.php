<?php

use DrobnyDev\ApplicationVersioning\ApplicationVersioning;
use DrobnyDev\ApplicationVersioning\Facades\ApplicationVersioning as ApplicationVersioningFacade;
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
