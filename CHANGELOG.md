# Changelog

All notable changes to `application-versioning` will be documented in this file.

## 0.3.0 - Unreleased

### Features

- **Scheme-aware `bump(?string $part)`.** A `major` of 2000 or more is calendar versioning: within the current year and month the patch goes up, otherwise the version becomes `year.month.1`. Anything lower is SemVer: the given part goes up and the parts below it reset to 0.
- **`--part` on `application-version:increase`.** `--part=major|minor|patch` bumps without a prompt. A calendar version is bumped without asking. With `--no-interaction` a SemVer version without `--part`, or a missing `version.yaml`, fails with exit code 1 instead of prompting.
- **`getFormattedVersion()`** on the instance and the facade. It never throws.
- **`missing_version` config** (`APP_VERSIONING_MISSING`). Shown instead of the version when `version.yaml` is missing or invalid, and a warning is logged.
- **Lock around a bump.** Two bumps at the same time no longer overwrite each other. A lock held for 5 seconds throws `LockTimeoutException` and writes nothing.
- `VersionFileException` names the file (or disk and path) and the key that is missing or has the wrong type.
- Facade `@method` docblocks for IDEs and static analysis.

### Fixes

- `GitSupport::getCurrentHeadHash()` ignored `$hashLength` once a hash was cached.
- The git hash stayed stale for up to 10 minutes after a deploy, under one global cache key shared by every app on the same cache. It is now read once per process and no longer touches Laravel Cache.
- `increaseMinor()` and `increaseMajor()` did not reset the lower parts (`1.4.7` became `1.5.7`).
- A missing `version.yaml` made `getFormatedVersion()` throw `ErrorException`, which broke any page that shows the version.

### Upgrading

- `increaseMajor()` and `increaseMinor()` now reset the lower parts. On a calendar version all `increase*` methods follow the calendar rule.
- `getFormatedVersion()` is deprecated. Use `getFormattedVersion()`.
- `spatie/laravel-package-tools` is no longer a dependency. The command names and the `application-versioning-config` publish tag are unchanged. The install command no longer asks to star the repository.
- `GitSupport::CACHE_KEY` and `GitSupport::CACHE_TTL_SECONDS` are deprecated and unused.
- An already published `config/application-versioning.php` needs no change. The package merges its own defaults under it (`mergeConfigFrom`), so `missing_version` defaults to an empty string and can be set with `APP_VERSIONING_MISSING`.
- `getFormattedVersion()` now catches every error while reading the version (an unknown disk, a storage error, an unreadable file), logs a warning and returns `missing_version`.
- `application-version:increase` also refuses to prompt when STDIN is not a terminal (CI, git hooks, `ssh host php artisan …` without `-n`), instead of applying the default part.

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
