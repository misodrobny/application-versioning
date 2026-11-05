<?php

namespace DrobnyDev\ApplicationVersioning;

use DrobnyDev\ApplicationVersioning\Exceptions\InvalidArgumentException;
use DrobnyDev\ApplicationVersioning\Exceptions\VersionFileException;
use DrobnyDev\ApplicationVersioning\Support\GitSupport;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Bound as a singleton by the service provider: resolve it from the container
 * (or use the facade) rather than instantiating it.
 */
class ApplicationVersioning
{
    const string MAJOR = 'major';

    const string MINOR = 'minor';

    const string PATCH = 'patch';

    const string GIT_HASH = 'git_hash';

    const string DEFAULT_FORMAT = '$major.$minor.$patch - $git_hash';

    public string $versionFilePath;

    public ?string $disk;

    public string $diskPath;

    public function __construct()
    {
        $this->versionFilePath = (string) config('application-versioning.version_file_path');
        $this->disk = config('application-versioning.disk') ?: null;
        $this->diskPath = (string) config('application-versioning.disk_path', 'version.yaml');
    }

    /**
     * @throws InvalidArgumentException|VersionFileException|LockTimeoutException
     */
    public function increaseMajor(): void
    {
        $this->bump(self::MAJOR);
    }

    /**
     * @throws InvalidArgumentException|VersionFileException|LockTimeoutException
     */
    public function increaseMinor(): void
    {
        $this->bump(self::MINOR);
    }

    /**
     * @throws InvalidArgumentException|VersionFileException|LockTimeoutException
     */
    public function increasePatch(): void
    {
        $this->bump(self::PATCH);
    }

    public function exists(): bool
    {
        return $this->disk === null
            ? file_exists($this->versionFilePath)
            : $this->filesystem()->exists($this->diskPath);
    }

    /**
     * Writes a fresh version.yaml, numbered with the current year and month.
     */
    public function create(): void
    {
        $this->write(Yaml::dump(['version' => ['current' => [
            self::MAJOR => (int) date('Y'),
            self::MINOR => (int) date('n'),
            self::PATCH => 1,
            'format' => self::DEFAULT_FORMAT,
        ]]], 2));
    }

    /**
     * The parsed version.yaml, after checking that version.current holds
     * integer major, minor and patch and a string format.
     *
     * @return array<mixed>
     *
     * @throws VersionFileException
     */
    public function getYamlContent(): array
    {
        if (! $this->exists()) {
            throw new VersionFileException("version.yaml not found at {$this->location()}.");
        }

        try {
            $content = Yaml::parse($this->read());
        } catch (ParseException $e) {
            throw new VersionFileException("version.yaml at {$this->location()} is not valid YAML: {$e->getMessage()}", previous: $e);
        }

        $current = is_array($content) ? ($content['version']['current'] ?? null) : null;

        if (! is_array($current)) {
            throw new VersionFileException("version.yaml at {$this->location()} has no version.current map.");
        }

        foreach ([self::MAJOR, self::MINOR, self::PATCH] as $part) {
            if (! is_int($current[$part] ?? null)) {
                throw new VersionFileException("version.yaml at {$this->location()} needs an integer version.current.{$part}.");
            }
        }

        if (! is_string($current['format'] ?? null)) {
            throw new VersionFileException("version.yaml at {$this->location()} needs a string version.current.format.");
        }

        return $content;
    }

    /**
     * True for calendar versioning (major is a year), false for SemVer.
     *
     * @throws VersionFileException
     */
    public function isCalendar(): bool
    {
        return $this->getYamlContent()['version']['current'][self::MAJOR] >= 2000;
    }

    /**
     * @throws VersionFileException
     */
    public function getFormat(): string
    {
        return $this->getYamlContent()['version']['current']['format'];
    }

    /**
     * @throws InvalidArgumentException|VersionFileException|LockTimeoutException
     */
    public function increaseVersion(string $type): void
    {
        $this->bump($type);
    }

    /**
     * Bumps the version and writes it back.
     *
     * Calendar versions (major >= 2000) ignore $part: within the current year
     * and month the patch goes up, otherwise the version becomes year.month.1.
     * SemVer needs $part and resets the parts below it.
     *
     * @throws InvalidArgumentException|VersionFileException|LockTimeoutException
     */
    public function bump(?string $part = null): void
    {
        if ($part !== null && ! in_array($part, [self::MAJOR, self::MINOR, self::PATCH], true)) {
            throw new InvalidArgumentException('Invalid version type');
        }

        $location = $this->disk === null ? $this->versionFilePath : "{$this->disk}|{$this->diskPath}";
        $write = function () use ($part): void {
            $content = $this->getYamlContent();
            $content['version']['current'] = array_merge(
                $content['version']['current'],
                $this->nextVersion($content['version']['current'], $part),
            );

            $this->write(Yaml::dump($content, 2));
        };

        $store = Cache::getStore();

        if (! $store instanceof LockProvider) {
            $write();

            return;
        }

        $store->lock('application-versioning:bump:'.md5($location), 10)->block(5, $write);
    }

    /**
     * @param  array<string, mixed>  $current
     * @return array{major: int, minor: int, patch: int}
     *
     * @throws InvalidArgumentException
     */
    protected function nextVersion(array $current, ?string $part): array
    {
        [$major, $minor, $patch] = [$current[self::MAJOR], $current[self::MINOR], $current[self::PATCH]];

        if ($major >= 2000) {
            $now = now();

            return $major === $now->year && $minor === $now->month
                ? [self::MAJOR => $major, self::MINOR => $minor, self::PATCH => $patch + 1]
                : [self::MAJOR => $now->year, self::MINOR => $now->month, self::PATCH => 1];
        }

        return match ($part) {
            self::MAJOR => [self::MAJOR => $major + 1, self::MINOR => 0, self::PATCH => 0],
            self::MINOR => [self::MAJOR => $major, self::MINOR => $minor + 1, self::PATCH => 0],
            self::PATCH => [self::MAJOR => $major, self::MINOR => $minor, self::PATCH => $patch + 1],
            default => throw new InvalidArgumentException('A SemVer version needs the part to bump: major, minor or patch.'),
        };
    }

    /**
     * @return string[]
     */
    public function getVersionConstants(): array
    {
        return [
            '$'.self::MAJOR,
            '$'.self::MINOR,
            '$'.self::PATCH,
            '$'.self::GIT_HASH,
        ];
    }

    /**
     * @return array{int, int, int, ?string}
     *
     * @throws VersionFileException
     */
    public function getCurrentVersions(): array
    {
        $current = $this->getYamlContent()['version']['current'];

        return [
            $current[self::MAJOR],
            $current[self::MINOR],
            $current[self::PATCH],
            GitSupport::getCurrentHeadHash(),
        ];
    }

    /**
     * The version in the file's format. Never throws: a missing or invalid
     * version.yaml logs a warning and returns the missing_version config value.
     */
    public function getFormattedVersion(): string
    {
        try {
            return str_replace(
                search: $this->getVersionConstants(),
                replace: array_map(strval(...), $this->getCurrentVersions()),
                subject: $this->getFormat());
        } catch (VersionFileException $e) {
            Log::warning($e->getMessage());

            return (string) config('application-versioning.missing_version', '');
        }
    }

    /**
     * @deprecated Since 0.3.0. Use getFormattedVersion() on the instance or the facade.
     */
    public static function getFormatedVersion(): string
    {
        return app(self::class)->getFormattedVersion();
    }

    /**
     * Where version.yaml lives, for messages.
     */
    protected function location(): string
    {
        return $this->disk === null
            ? $this->versionFilePath
            : "disk {$this->disk}: {$this->diskPath}";
    }

    protected function read(): string
    {
        return $this->disk === null
            ? (string) file_get_contents($this->versionFilePath)
            : (string) $this->filesystem()->get($this->diskPath);
    }

    protected function write(string $contents): void
    {
        if ($this->disk === null) {
            file_put_contents($this->versionFilePath, $contents);

            return;
        }

        $this->filesystem()->put($this->diskPath, $contents);
    }

    protected function filesystem(): Filesystem
    {
        return Storage::disk($this->disk);
    }
}
