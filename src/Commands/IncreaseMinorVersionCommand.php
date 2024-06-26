<?php

namespace DrobnyDev\ApplicationVersioning\Commands;

use DrobnyDev\ApplicationVersioning\ApplicationVersioning;
use DrobnyDev\ApplicationVersioning\Exceptions\InvalidArgumentException;
use Illuminate\Console\Command;

class IncreaseMinorVersionCommand extends Command
{
    protected $signature = 'application-version:increase-minor';

    protected $description = 'Increase minor version';

    /**
     * @throws InvalidArgumentException
     */
    public function handle(): void
    {
        (new ApplicationVersioning())->increaseMinor();
        $this->info('Minor version increased. Current version is now: '. ApplicationVersioning::getFormatedVersion());
    }
}
