<?php

namespace DrobnyDev\ApplicationVersioning;

use DrobnyDev\ApplicationVersioning\Exceptions\InvalidArgumentException;
use DrobnyDev\ApplicationVersioning\Support\GitSupport;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
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

    // region ---- Private members ----
    public string $versionFilePath;

    public ?string $disk;

    public string $diskPath;

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

    public function getYamlContent(): mixed
    {
        return Yaml::parse($this->read());
    }

    public function getFormat(): string
    {
        $yamlContents = $this->getYamlContent();

        return $yamlContents['version']['current']['format'];
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
     * @return string[]
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getCurrentVersions(): array
    {
        $yamlContents = $this->getYamlContent();

        return [
            $yamlContents['version']['current']['major'],
            $yamlContents['version']['current']['minor'],
            $yamlContents['version']['current']['patch'],
            GitSupport::getCurrentHeadHash(),
        ];
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
    // endregion

    // region ---- Static members ----
    /**
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public static function getFormatedVersion(): string
    {
        $self = app(self::class);

        return str_replace(
            search: $self->getVersionConstants(),
            replace: $self->getCurrentVersions(),
            subject: $self->getFormat());
    }
    // endregion
}
