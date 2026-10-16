<?php

namespace DrobnyDev\ApplicationVersioning;

use DrobnyDev\ApplicationVersioning\Commands\IncreaseVersionCommand;
use Exception;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ApplicationVersioningServiceProvider extends PackageServiceProvider
{
    public function packageRegistered(): void
    {
        $this->app->singleton(ApplicationVersioning::class, fn () => new ApplicationVersioning);
    }

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('application-versioning')
            ->hasConfigFile('application-versioning')
            ->hasCommand(IncreaseVersionCommand::class)
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->startWith(function (InstallCommand $command) {
                        $command->info('Hello, and welcome to Application Versioning package for Laravel!');

                        $versioning = app(ApplicationVersioning::class);

                        if (! $versioning->exists()) {
                            try {
                                $versioning->create();

                                $command->info('version.yaml file created successfully.');
                            } catch (Exception) {
                                $command->error('Could not create version.yaml file. Please create it manually.');
                            }
                        } else {
                            $command->info('version.yaml file already exists.');
                        }
                    })
                    ->publishConfigFile()
                    ->askToStarRepoOnGitHub('misodrobny/application-versioning');
            });
    }
}
