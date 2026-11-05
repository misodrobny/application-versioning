<?php

namespace DrobnyDev\ApplicationVersioning;

use DrobnyDev\ApplicationVersioning\Commands\IncreaseVersionCommand;
use DrobnyDev\ApplicationVersioning\Commands\InstallCommand;
use Illuminate\Contracts\Foundation\CachesConfiguration;
use Illuminate\Support\ServiceProvider;

class ApplicationVersioningServiceProvider extends ServiceProvider
{
    protected const string CONFIG = __DIR__.'/../config/application-versioning.php';

    public function register(): void
    {
        if (! ($this->app instanceof CachesConfiguration && $this->app->configurationIsCached())) {
            $this->mergeConfigFrom(self::CONFIG, 'application-versioning');
        }

        $this->app->singleton(ApplicationVersioning::class, fn () => new ApplicationVersioning);
    }

    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([self::CONFIG => config_path('application-versioning.php')], 'application-versioning-config');

        $this->commands([InstallCommand::class, IncreaseVersionCommand::class]);
    }
}
