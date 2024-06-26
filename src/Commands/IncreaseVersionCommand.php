<?php

namespace DrobnyDev\ApplicationVersioning\Commands;

use DrobnyDev\ApplicationVersioning\ApplicationVersioning;
use DrobnyDev\ApplicationVersioning\Exceptions\InvalidArgumentException;
use Illuminate\Console\Command;

class IncreaseVersionCommand extends Command
{
    protected $signature = 'application-version:increase-version {--T|type= : major, minor or patch}';

    protected $description = 'Increase major, minor or patch version';

    /**
     * @throws InvalidArgumentException
     */
    public function handle(): void
    {
        switch ($this->option('type')) {
            case '=major':
                (new ApplicationVersioning())->increaseMajor();
                $this->message('Major');
                break;
            case '=minor':
                (new ApplicationVersioning())->increaseMinor();
                $this->message('Minor');
                break;
            case '=patch':
                (new ApplicationVersioning())->increasePatch();
                $this->message('Patch');
                break;
            default:
                $this->info('Please specify version type: major, minor or patch.');
        }
    }

    private function message(string $type): void
    {
        $this->info("$type version increased. Current version is now: ". ApplicationVersioning::getFormatedVersion());
    }
}
