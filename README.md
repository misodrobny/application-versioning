# This is my package Application versioning

[![Latest Version on Packagist](https://img.shields.io/packagist/v/misodrobny/application-versioning.svg?style=flat-square)](https://packagist.org/packages/misodrobny/application-versioning)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/misodrobny/application-versioning/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/misodrobny/application-versioning/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/misodrobny/application-versioning/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/misodrobny/application-versioning/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/misodrobny/application-versioning.svg?style=flat-square)](https://packagist.org/packages/misodrobny/application-versioning)

This package will help to manage version of your application. It will create version.yaml file in root of your project and you can manage version of your application in this file.

For more about application versioning please follow [Semantic Versioning](https://semver.org/)

## Installation

You can install the package via composer:

```bash
composer require misodrobny/application-versioning
```

After installation, you need to run install script which will publish config file and create default version.yaml file.
```bash
php artisan application-versioning:install
```

This is the contents of the published config file:

```php
return [
    'version_file_path' => base_path('version.yaml'),
    'disk' => env('APP_VERSIONING_DISK'),
    'disk_path' => env('APP_VERSIONING_DISK_PATH', 'version.yaml'),
    'git_path' => env('APP_VERSIONING_GIT_PATH', base_path().'/.git'),
];
```

Initial formated version has following structure:
```php
$major.$minor.$patch - $git_hash
```
`$git_hash` contains 7 characters long GIT HASH from current git commit. It is read from a checked out branch, from a ref stored only in `packed-refs`, and from a detached HEAD (a deployment that checks out a commit). When no hash can be read, `$git_hash` is empty instead of an exception being thrown.

### Storage disk

By default `version.yaml` is a local file at `version_file_path`. To read and write it through a Laravel filesystem disk instead (for example when several servers share it), set the disk and the path on that disk:

```dotenv
APP_VERSIONING_DISK=s3
APP_VERSIONING_DISK_PATH=my-app/version.yaml
```

The install command, the increase command and all methods below then use that disk.

## Usage

`ApplicationVersioning` is registered as a singleton. Resolve it from the container or use the facade `ApplicationVersion` instead of instantiating it.

For getting version of your application:
```php
    echo ApplicationVersion::getFormatedVersion();
```

To increase version of your application you can use following methods:
```php
    use DrobnyDev\ApplicationVersioning\ApplicationVersioning;

    app(ApplicationVersioning::class)->increaseMajor();
    app(ApplicationVersioning::class)->increaseMinor();
    app(ApplicationVersioning::class)->increasePatch();
```
It is also possible to run 
```bash
php artisan application-version:increase 
```
This command will generate Laravel Prompt window for choosing which version needs update:

```bash
┌ Which version do you want to increase? ──────────────────────┐
│   ○ major                                                    │
│   ○ minor                                                    │
│ › ● patch                                                    │
└──────────────────────────────────────────────────────────────┘
```
## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Michal Drobny](https://github.com/54170028+misodrobny)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
