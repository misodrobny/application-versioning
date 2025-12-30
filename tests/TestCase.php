<?php

namespace DrobnyDev\ApplicationVersioning\Tests;

use DrobnyDev\ApplicationVersioning\ApplicationVersioningServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            ApplicationVersioningServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void {}
}
