<?php

namespace DrobnyDev\ApplicationVersioning\Tests;

use DrobnyDev\ApplicationVersioning\ApplicationVersioningServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            ApplicationVersioningServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
    }
}
