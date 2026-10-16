<?php

namespace DrobnyDev\ApplicationVersioning\Commands;

use DrobnyDev\ApplicationVersioning\ApplicationVersioning;
use DrobnyDev\ApplicationVersioning\Exceptions\InvalidArgumentException;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\select;

class IncreaseVersionCommand extends Command
{
    protected $signature = 'application-version:increase';

    protected $description = 'Increase major, minor or patch version';

    /**
     * @throws InvalidArgumentException|\Psr\SimpleCache\InvalidArgumentException
     */
    public function handle(): void
    {
        $versioning = app(ApplicationVersioning::class);

        if (! $versioning->exists()) {
            if (confirm('version.yaml not found. Do you want to run the installation command?')) {
                $this->call('application-versioning:install');
            } else {
                $this->warn('Aborted. version.yaml is required.');

                return;
            }
        }

        $versionType = select(
            label: 'Which version do you want to increase?',
            options: ['major', 'minor', 'patch'],
            default: 'patch'
        );

        switch ($versionType) {
            case 'major':
                $versioning->increaseMajor();
                break;
            case 'minor':
                $versioning->increaseMinor();
                break;
            case 'patch':
                $versioning->increasePatch();
                break;
        }

        info(ucfirst($versionType).' version increased. Current version is now: '.ApplicationVersioning::getFormatedVersion());
    }
}
