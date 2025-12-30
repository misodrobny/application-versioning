<?php

namespace DrobnyDev\ApplicationVersioning\Commands;

use DrobnyDev\ApplicationVersioning\ApplicationVersioning;
use DrobnyDev\ApplicationVersioning\Exceptions\InvalidArgumentException;
use Illuminate\Console\Command;
use function Laravel\Prompts\info;
use function Laravel\Prompts\select;

class IncreaseVersionCommand extends Command
{
    protected $signature = 'application-version:increase';

    protected $description = 'Increase major, minor or patch version';

    /**
     * @throws InvalidArgumentException
     */
    public function handle(): void
    {
        $versionType = select(
            label: 'Which version do you want to increase?',
            options: ['major', 'minor', 'patch'],
            default: 'patch'
        );

        switch ($versionType) {
            case 'major':
                (new ApplicationVersioning)->increaseMajor();
                break;
            case 'minor':
                (new ApplicationVersioning)->increaseMinor();
                break;
            case 'patch':
                (new ApplicationVersioning)->increasePatch();
                break;
        }

        info(ucfirst($versionType).' version increased. Current version is now: '.ApplicationVersioning::getFormatedVersion());
    }
}
