<?php

namespace DrobnyDev\ApplicationVersioning\Commands;

use DrobnyDev\ApplicationVersioning\ApplicationVersioning;
use DrobnyDev\ApplicationVersioning\Exceptions\InvalidArgumentException;
use Illuminate\Console\Command;

class IncreaseMajorVersionCommand extends Command
{
    protected $signature = 'application-version:increase-major';

    protected $description = 'Increase major version';

    /**
     * @throws InvalidArgumentException
     */
    public function handle(): void
    {
        (new ApplicationVersioning)->increaseMajor();
        $this->info('Major version increased. Current version is now: '.ApplicationVersioning::getFormatedVersion());
    }
}
