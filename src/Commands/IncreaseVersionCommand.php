<?php

namespace DrobnyDev\ApplicationVersioning\Commands;

use DrobnyDev\ApplicationVersioning\ApplicationVersioning;
use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Throwable;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;

class IncreaseVersionCommand extends Command
{
    protected $signature = 'application-version:increase {--part= : major, minor or patch}';

    protected $description = 'Increase the version: calendar versions by date, SemVer by the given part';

    private const array PARTS = [ApplicationVersioning::MAJOR, ApplicationVersioning::MINOR, ApplicationVersioning::PATCH];

    public function handle(ApplicationVersioning $versioning): int
    {
        $part = $this->option('part');

        if ($part !== null && ! in_array($part, self::PARTS, true)) {
            $this->error("Unknown part \"{$part}\". Use major, minor or patch.");

            return self::FAILURE;
        }

        if (! $versioning->exists()) {
            if (! $this->canPrompt()) {
                $this->error('version.yaml not found. Run php artisan application-versioning:install first.');

                return self::FAILURE;
            }

            if (! confirm('version.yaml not found. Do you want to run the installation command?')) {
                $this->warn('Aborted. version.yaml is required.');

                return self::FAILURE;
            }

            $this->call('application-versioning:install');
        }

        try {
            $old = $versioning->getFormattedVersion();

            if (! $versioning->isCalendar() && $part === null) {
                if (! $this->canPrompt()) {
                    $this->error('A SemVer version needs --part=major, --part=minor or --part=patch.');

                    return self::FAILURE;
                }

                // A list of options makes select() return the chosen value, one of PARTS.
                $choice = select(label: 'Which version do you want to increase?', options: self::PARTS, default: ApplicationVersioning::PATCH);
                $part = in_array($choice, self::PARTS, true) ? $choice : null;
            }

            $versioning->bump($part);
        } catch (LockTimeoutException) {
            $this->error('Another bump holds the version.yaml lock. Try again.');

            return self::FAILURE;
        } catch (Throwable $e) {
            // Includes a cache store that fails to lock, e.g. a missing cache_locks table.
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Version increased: {$old} → {$versioning->getFormattedVersion()}");

        return self::SUCCESS;
    }

    /**
     * Whether a prompt would really ask. Laravel turns prompts off when STDIN
     * is not a terminal, outside tests (ConfiguresPrompts::configurePrompts),
     * and select() then returns its default without asking.
     */
    protected function canPrompt(): bool
    {
        return $this->input->isInteractive()
            && ((defined('STDIN') && stream_isatty(STDIN)) || $this->laravel->runningUnitTests());
    }
}
