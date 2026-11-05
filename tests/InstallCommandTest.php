<?php

use DrobnyDev\ApplicationVersioning\ApplicationVersioning;

beforeEach(function () {
    $this->versionFile = sys_get_temp_dir().'/av-install-'.bin2hex(random_bytes(4)).'.yaml';
    config(['application-versioning.version_file_path' => $this->versionFile, 'application-versioning.disk' => null]);
    app()->forgetInstance(ApplicationVersioning::class);
    @unlink(config_path('application-versioning.php'));
});

afterEach(function () {
    @unlink($this->versionFile);
    @unlink(config_path('application-versioning.php'));
});

it('creates version.yaml and publishes the config', function () {
    $this->artisan('application-versioning:install')
        ->expectsOutputToContain('version.yaml file created')
        ->assertExitCode(0);

    expect(app(ApplicationVersioning::class)->exists())->toBeTrue()
        ->and(file_exists(config_path('application-versioning.php')))->toBeTrue();
});

it('leaves an existing version.yaml alone', function () {
    file_put_contents($this->versionFile, "version:\n    current: { major: 1, minor: 2, patch: 3, format: x }\n");

    $this->artisan('application-versioning:install')
        ->expectsOutputToContain('already exists')
        ->assertExitCode(0);

    expect(file_get_contents($this->versionFile))->toBe("version:\n    current: { major: 1, minor: 2, patch: 3, format: x }\n");
});

it('does not ask to star the repository', function () {
    $this->artisan('application-versioning:install')
        ->doesntExpectOutputToContain('star')
        ->assertExitCode(0);
});
