<?php

namespace Michal Drobny\ApplicationVersioning\Commands;

use Illuminate\Console\Command;

class ApplicationVersioningCommand extends Command
{
    public $signature = 'application-versioning';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
