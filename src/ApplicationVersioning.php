<?php

namespace DrobnyDev\ApplicationVersioning;

use DrobnyDev\ApplicationVersioning\Exceptions\InvalidArgumentException;
use DrobnyDev\ApplicationVersioning\Exceptions\VersionFileException;
use DrobnyDev\ApplicationVersioning\Support\GitSupport;
use Illuminate\Contracts\Filesystem\Filesystem;
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
     * @throws InvalidArgumentException
     */
    public function increaseMajor(): void
    {
        $this->increaseVersion(self::MAJOR);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function increaseMinor(): void
    {
        $this->increaseVersion(self::MINOR);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function increasePatch(): void
    {
        $this->increaseVersion(self::PATCH);
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
     * @throws InvalidArgumentException
     */
    public function increaseVersion(string $type): void
    {
        if ($type !== self::MAJOR && $type !== self::MINOR && $type !== self::PATCH) {
            throw new InvalidArgumentException('Invalid version type');
        }

        $yamlContents = $this->getYamlContent();
        $yamlContents['version']['current'][$type]++;

        $this->write(Yaml::dump($yamlContents));
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
