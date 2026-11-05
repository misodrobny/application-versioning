# Application Versioning for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/misodrobny/application-versioning.svg?style=flat-square)](https://packagist.org/packages/misodrobny/application-versioning)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/misodrobny/application-versioning/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/misodrobny/application-versioning/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/misodrobny/application-versioning/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/misodrobny/application-versioning/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/misodrobny/application-versioning.svg?style=flat-square)](https://packagist.org/packages/misodrobny/application-versioning)

Keeps your application's version in a `version.yaml` file, bumps it with calendar or semantic versioning, and shows it together with the current git commit hash.

## Installation

```bash
composer require misodrobny/application-versioning
```

Then run the install command. It creates `version.yaml` (numbered with the current year and month) and publishes the config file:

```bash
php artisan application-versioning:install
```

The published config file `config/application-versioning.php`:

```php
return [
    'version_file_path' => base_path('version.yaml'),
    'disk' => env('APP_VERSIONING_DISK'),
    'disk_path' => env('APP_VERSIONING_DISK_PATH', 'version.yaml'),
    'git_path' => env('APP_VERSIONING_GIT_PATH', base_path().'/.git'),
    'missing_version' => env('APP_VERSIONING_MISSING', ''),
];
```

## The version.yaml file

```yaml
version:
    current: { major: 2026, minor: 11, patch: 1, format: '$major.$minor.$patch - $git_hash' }
```

`major`, `minor` and `patch` must be integers and `format` a string. Other keys in the file are kept when the version is bumped.

`format` may use `$major`, `$minor`, `$patch` and `$git_hash`. `$git_hash` is the first 7 characters of the current commit. It is read from a checked out branch, from a ref stored only in `packed-refs`, and from a detached HEAD (a deployment that checks out a commit). When no hash can be read, `$git_hash` is empty.

The hash is read once per PHP process and kept in memory: once per request under PHP-FPM. Under Octane a worker keeps the hash until it restarts, so reload the workers on deploy (`php artisan octane:reload`).

## Showing the version

```php
use DrobnyDev\ApplicationVersioning\Facades\ApplicationVersioning;

echo ApplicationVersioning::getFormattedVersion();
```

The facade is also aliased as `ApplicationVersion`. `ApplicationVersioning` is registered as a singleton: resolve it from the container or use the facade instead of instantiating it.

`getFormattedVersion()` never throws. When `version.yaml` is missing or invalid it logs a warning and returns `missing_version` (empty by default, e.g. `APP_VERSIONING_MISSING=dev`).

`getFormatedVersion()` still works, but is deprecated since 0.3.0.

## Bumping the version

The scheme is detected from the file: a `major` of 2000 or more is calendar versioning, anything lower is semantic versioning.

| Scheme | Rule |
|---|---|
| Calendar (`2026.11.3`) | Same year and month as today: `patch + 1`. Otherwise `year.month.1`. The part is ignored. |
| SemVer (`1.4.7`) | `major` → `2.0.0`, `minor` → `1.5.0`, `patch` → `1.4.8`. The part is required. |

From the command line:

```bash
php artisan application-version:increase              # calendar: no question asked
php artisan application-version:increase --part=minor # SemVer
```

Without `--part`, a SemVer version asks which part to bump. With `--no-interaction` (CI, deploy scripts) it fails instead, and so does a missing `version.yaml`. The command exits with 1 on any error.

From code:

```php
use DrobnyDev\ApplicationVersioning\ApplicationVersioning;

app(ApplicationVersioning::class)->bump();         // calendar
app(ApplicationVersioning::class)->bump('minor');  // SemVer
```

`increaseMajor()`, `increaseMinor()` and `increasePatch()` call `bump()` with that part.

A bump holds a cache lock while it reads and writes the file, so two bumps at the same time do not overwrite each other. If the lock stays taken for 5 seconds, `Illuminate\Contracts\Cache\LockTimeoutException` is thrown and nothing is written. Cache stores without lock support bump without a lock.

### Storage disk

By default `version.yaml` is a local file at `version_file_path`. To read and write it through a Laravel filesystem disk instead (for example when several servers share it), set the disk and the path on that disk:

```dotenv
APP_VERSIONING_DISK=s3
APP_VERSIONING_DISK_PATH=my-app/version.yaml
```

The install command, the increase command and all methods above then use that disk.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Michal Drobny](https://github.com/misodrobny)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
