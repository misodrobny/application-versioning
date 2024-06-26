<?php

namespace DrobnyDev\ApplicationVersioning\Commands;

use DrobnyDev\ApplicationVersioning\ApplicationVersioning;
use DrobnyDev\ApplicationVersioning\Exceptions\InvalidArgumentException;
use Illuminate\Console\Command;

class IncreasePatchVersionCommand extends Command
{
    protected $signature = 'application-version:patch';

    protected $description = 'Increase patch version';

    /**
     * @throws InvalidArgumentException
     */
    public function handle(): void
    {
        (new ApplicationVersioning())->increasePatch();
    }
}
