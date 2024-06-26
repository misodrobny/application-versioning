<?php

namespace DrobnyDev\ApplicationVersioning;

use DrobnyDev\ApplicationVersioning\Exceptions\InvalidArgumentException;
use Symfony\Component\Yaml\Yaml;

class ApplicationVersioning
{
    const string MAJOR = 'major';

    const string MINOR = 'minor';

    const string PATCH = 'patch';

    public function __construct()
    {
        $this->versionFilePath = config('application-versioning.version_file_path');
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

    //region ---- Private members ----
    private string $versionFilePath;

    private function getYamlContent(): mixed
    {
        return Yaml::parse(file_get_contents($this->versionFilePath));
    }

    private function getFormat(): string
    {
        $yamlContents = $this->getYamlContent();

        return $yamlContents['version']['current']['format'];
    }

    /**
     * @throws InvalidArgumentException
     */
    private function increaseVersion(string $type): void
    {
        if ($type !== self::MAJOR && $type !== self::MINOR && $type !== self::PATCH) {
            throw new InvalidArgumentException('Invalid version type');
        }

        $yamlContents = $this->getYamlContent();
        $yamlContents['version']['current'][$type]++;

        file_put_contents($this->versionFilePath, Yaml::dump($yamlContents));
    }

    /**
     * @return string[]
     */
    private function getVersionConstants(): array
    {
        return [
            '$'.self::MAJOR,
            '$'.self::MINOR,
            '$'.self::PATCH,
        ];
    }

    /**
     * @return string[]
     */
    private function getCurrentVersions(): array
    {
        $yamlContents = $this->getYamlContent();

        return [
            $yamlContents['version']['current']['major'],
            $yamlContents['version']['current']['minor'],
            $yamlContents['version']['current']['patch'],
        ];
    }
    // endregion

    //region ---- Static members ----
    public static function getFormatedVersion(): string
    {
        $self = new ApplicationVersioning();

        return str_replace(
            search: $self->getVersionConstants(),
            replace: $self->getCurrentVersions(),
            subject: $self->getFormat());
    }
    // endregion
}
