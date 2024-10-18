<?php

namespace DrobnyDev\ApplicationVersioning\Support;

use Illuminate\Support\Facades\Cache;

class GitSupport
{
    public const string CACHE_KEY = 'GitSupport.CurrentHash';

    public const int CACHE_TTL_SECONDS = 600; // 10 minutes

    /**
     * Examines .git folder and returns current heads' hash.
     *
     * @param  bool  $forceRefresh  If TRUE, cache is forced refreshed
     * @param  int  $hashLength  Required length of hash to return
     * @param  mixed|null  $default  Default value to return if no hash available
     * @return string|null Current heads' hash
     */
    public static function getCurrentHeadHash(
        bool $forceRefresh = false,
        int $hashLength = 7,
        mixed $default = null
    ): ?string {
        if (! $forceRefresh && Cache::has(static::CACHE_KEY)) {
            return Cache::get(static::CACHE_KEY);
        }

        $gitDir = config('application-versioning.git_path');
        if (! is_dir($gitDir)) {
            return $default;
        }

        $headFile = $gitDir.'/HEAD';
        if (! file_exists($headFile)) {
            return $default;
        }

        preg_match('#^ref:(.+)$#', @file_get_contents($headFile), $matches);
        $currentHeadFile = $gitDir.'/'.trim($matches[1]);
        if (! file_exists($currentHeadFile)) {
            return $default;
        }

        $currentHash = substr(@file_get_contents($currentHeadFile), 0, $hashLength);
        /** @noinspection PhpParamsInspection */
        // @phpstan-ignore-next-line
        Cache::set(static::CACHE_KEY, $currentHash, now()->addSeconds(static::CACHE_TTL_SECONDS));

        return $currentHash;
    }
}
