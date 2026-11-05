<?php

use DrobnyDev\ApplicationVersioning\ApplicationVersioning;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->versionFile = sys_get_temp_dir().'/av-cmd-'.bin2hex(random_bytes(4)).'.yaml';
    config([
        'application-versioning.version_file_path' => $this->versionFile,
        'application-versioning.disk' => null,
        'application-versioning.git_path' => sys_get_temp_dir().'/av-no-git',
    ]);
    app()->forgetInstance(ApplicationVersioning::class);
    $this->travelTo(Carbon::create(2026, 10, 20));

    $this->write = fn (string $version) => file_put_contents($this->versionFile, 'version:'."\n".'    current: { major: '.str_replace('.', ', minor: ', preg_replace('/\.(\d+)$/', ', patch: $1', $version)).', format: $major.$minor.$patch }'."\n");
});

afterEach(function () {
    @unlink($this->versionFile);
});

it('bumps the given SemVer part', function () {
    ($this->write)('1.4.7');

    $this->artisan('application-version:increase', ['--part' => 'minor'])
        ->expectsOutputToContain('1.4.7 → 1.5.0')
        ->assertExitCode(0);

    expect(app(ApplicationVersioning::class)->getFormattedVersion())->toBe('1.5.0');
});

it('bumps a calendar version without asking', function (array $options) {
    ($this->write)('2026.10.3');

    $this->artisan('application-version:increase', $options)->assertExitCode(0);

    expect(app(ApplicationVersioning::class)->getFormattedVersion())->toBe('2026.10.4');
})->with([
    'no part' => [[]],
    'part ignored' => [['--part' => 'major']],
]);

it('rejects an unknown part', function () {
    ($this->write)('1.4.7');

    $this->artisan('application-version:increase', ['--part' => 'build'])->assertExitCode(1);

    expect(app(ApplicationVersioning::class)->getFormattedVersion())->toBe('1.4.7');
});

it('fails without --part on SemVer when not interactive', function () {
    ($this->write)('1.4.7');

    $this->artisan('application-version:increase', ['--no-interaction' => true])->assertExitCode(1);

    expect(app(ApplicationVersioning::class)->getFormattedVersion())->toBe('1.4.7');
});

it('asks for the part on SemVer when interactive', function () {
    ($this->write)('1.4.7');

    $this->artisan('application-version:increase')
        ->expectsChoice('Which version do you want to increase?', 'patch', ['major', 'minor', 'patch'])
        ->assertExitCode(0);

    expect(app(ApplicationVersioning::class)->getFormattedVersion())->toBe('1.4.8');
});

it('fails when version.yaml is missing and not interactive', function () {
    $this->artisan('application-version:increase', ['--part' => 'patch', '--no-interaction' => true])->assertExitCode(1);

    expect(file_exists($this->versionFile))->toBeFalse();
});
