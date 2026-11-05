<?php

namespace DrobnyDev\ApplicationVersioning\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string getFormattedVersion()
 * @method static string getFormatedVersion() Deprecated since 0.3.0, use getFormattedVersion().
 * @method static void bump(?string $part = null)
 * @method static void increaseMajor()
 * @method static void increaseMinor()
 * @method static void increasePatch()
 * @method static void increaseVersion(string $type)
 * @method static bool isCalendar()
 * @method static bool exists()
 * @method static void create()
 * @method static array<mixed> getYamlContent()
 * @method static string getFormat()
 * @method static string[] getVersionConstants()
 * @method static array{int, int, int, ?string} getCurrentVersions()
 *
 * @see \DrobnyDev\ApplicationVersioning\ApplicationVersioning
 */
class ApplicationVersioning extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \DrobnyDev\ApplicationVersioning\ApplicationVersioning::class;
    }
}
