# Changelog

All notable changes to `application-versioning` will be documented in this file.

## 0.2.0 - 2026-10-16

### Features

- **Storage disk support.** `version.yaml` can be read and written through any Laravel filesystem disk. Set `APP_VERSIONING_DISK` (and `APP_VERSIONING_DISK_PATH`, default `version.yaml`). Without a disk the local file at `version_file_path` is used as before.
- **Singleton.** `ApplicationVersioning` was already bound as a singleton, but `getFormatedVersion()`, the install command and the increase command created new instances. They now all resolve it from the container. Use `app(ApplicationVersioning::class)` or the `ApplicationVersion` facade.
- **Laravel 13 and Symfony 8.** `symfony/yaml` is now `^7.1|^8.0`, so the package installs on Laravel 13. Tests run on Laravel 11, 12 and 13.

### Fixes

- **Git hash on a detached HEAD.** `GitSupport::getCurrentHeadHash()` threw `Undefined array key 1` when `.git/HEAD` held a commit hash instead of `ref: …`, as deployments that check out a commit leave it. It now reads that hash.
- **Refs in `packed-refs`.** A branch whose ref exists only in `packed-refs` (after `git gc` or a fresh clone) now resolves to its hash instead of returning nothing.
- Anything unreadable in `.git` now returns the default value instead of throwing, so `getFormatedVersion()` keeps working with an empty `$git_hash`.

### Upgrading

No breaking changes. To use a disk, add `disk` and `disk_path` to a published `config/application-versioning.php`, or publish it again:

```php
'disk' => env('APP_VERSIONING_DISK'),
'disk_path' => env('APP_VERSIONING_DISK_PATH', 'version.yaml'),

```
## 0.1.1 - 2025-12-30

Fix: Missing new Readme

## 0.1.0 - 2025-12-30

Couple of usability update like single laravel prompt command.

## Hotfix: missing requirement - 2024-10-14

Fix:

- missing synfony/yaml requirement

## 0.0.1 - 2024-06-26

The initial version of packages provides basic functionality like creating a base **version.yaml** file and the ability to increase the version programmatically or via command.
