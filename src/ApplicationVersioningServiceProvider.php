<?php

namespace DrobnyDev\ApplicationVersioning;

use DrobnyDev\ApplicationVersioning\Commands\IncreaseMajorVersionCommand;
use DrobnyDev\ApplicationVersioning\Commands\IncreaseMinorVersionCommand;
use DrobnyDev\ApplicationVersioning\Commands\IncreasePatchVersionCommand;
use Exception;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

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
            ->hasConfigFile('application-versioning')
            ->hasCommand(IncreaseMajorVersionCommand::class)
            ->hasCommand(IncreaseMinorVersionCommand::class)
            ->hasCommand(IncreasePatchVersionCommand::class)
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->startWith(function (InstallCommand $command) {
                        $command->info('Hello, and welcome to Application Versioning package for Laravel!');

                        if (!file_exists(base_path('version.yaml')))
                        {
                            try {
                                file_put_contents(base_path('version.yaml'), "version:\n".
                                    "current: { major: ".date('Y').", minor: 0, patch: 0, format: \$major.\$minor.\$patch }");

                                $command->info('version.yaml file created successfully.');
                            }
                            catch (Exception)
                            {
                                $command->error('Could not create version.yaml file. Please create it manually.');
                            }
                        }
                        else
                        {
                            $command->info('version.yaml file already exists.');
                        }
                    })
                    ->publishConfigFile()
                    ->askToStarRepoOnGitHub('misodrobny/application-versioning');
            });
    }
}
