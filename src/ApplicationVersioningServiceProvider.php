<?php

namespace Michal Drobny\ApplicationVersioning;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Michal Drobny\ApplicationVersioning\Commands\ApplicationVersioningCommand;

class ApplicationVersioningServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('application-versioning')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_application-versioning_table')
            ->hasCommand(ApplicationVersioningCommand::class);
    }
}
