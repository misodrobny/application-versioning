<?php

use DrobnyDev\ApplicationVersioning\ApplicationVersioningServiceProvider;
use Illuminate\Contracts\Console\Kernel;
use Orchestra\Testbench\Foundation\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

/*
 * Runs application-version:increase in a real console process whose STDIN is
 * not a TTY and that is not a unit-test run, as a CI job or git hook does.
 * Usage: php increase-without-tty.php <version.yaml path>
 */

require __DIR__.'/../../vendor/autoload.php';

$app = Application::create(options: ['extra' => ['providers' => [
    ApplicationVersioningServiceProvider::class,
]]]);
$app['env'] = 'production';

config(['application-versioning.version_file_path' => $argv[1], 'cache.default' => 'array']);

exit($app->make(Kernel::class)->handle(
    new ArgvInput(['artisan', 'application-version:increase']),
    new ConsoleOutput,
));
