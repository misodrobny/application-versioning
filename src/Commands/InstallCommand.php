<?php

namespace DrobnyDev\ApplicationVersioning\Commands;

use DrobnyDev\ApplicationVersioning\ApplicationVersioning;
use Illuminate\Console\Command;
use Throwable;

class InstallCommand extends Command
{
    protected $signature = 'application-versioning:install';

    protected $description = 'Create version.yaml and publish the application-versioning config';

    public function handle(ApplicationVersioning $versioning): int
    {
        if ($versioning->exists()) {
            $this->info('version.yaml file already exists.');
        } else {
            try {
                $versioning->create();
            } catch (Throwable $e) {
                $this->error("Could not create version.yaml file: {$e->getMessage()}");

                return self::FAILURE;
            }

            $this->info('version.yaml file created successfully.');
        }

        $this->callSilently('vendor:publish', ['--tag' => 'application-versioning-config']);
        $this->info('Config file published to config/application-versioning.php.');

        return self::SUCCESS;
    }
}
