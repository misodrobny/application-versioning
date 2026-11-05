<?php

use DrobnyDev\ApplicationVersioning\ApplicationVersioning;
use DrobnyDev\ApplicationVersioning\ApplicationVersioningServiceProvider;
use Illuminate\Support\ServiceProvider;

it('merges the package config', function () {
    expect(config('application-versioning.missing_version'))->toBe('')
        ->and(config('application-versioning.disk_path'))->toBe('version.yaml');
});

it('publishes the config under the application-versioning-config tag', function () {
    $paths = ServiceProvider::pathsToPublish(ApplicationVersioningServiceProvider::class, 'application-versioning-config');

    expect($paths)->toHaveCount(1)
        ->and(array_key_first($paths))->toEndWith('config/application-versioning.php')
        ->and(reset($paths))->toBe(config_path('application-versioning.php'));
});

it('binds ApplicationVersioning as a singleton', function () {
    expect(app(ApplicationVersioning::class))->toBe(app(ApplicationVersioning::class));
});

it('extends the plain Laravel service provider', function () {
    expect(get_parent_class(ApplicationVersioningServiceProvider::class))->toBe(ServiceProvider::class);
});
