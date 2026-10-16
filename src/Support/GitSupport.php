<?php

namespace DrobnyDev\ApplicationVersioning\Support;

use Illuminate\Support\Facades\Cache;
use Psr\SimpleCache\InvalidArgumentException;

class GitSupport
{
    public const string CACHE_KEY = 'GitSupport.CurrentHash';

    public const int CACHE_TTL_SECONDS = 600; // 10 minutes

    /**
     * Examines .git folder and returns current heads' hash.
     *
     * Handles a branch checkout (HEAD holds "ref: refs/heads/main"), a ref that
     * only exists in packed-refs, and a detached HEAD (HEAD holds the commit
     * hash itself, as deployments that check out a commit leave it). Anything
     * unreadable returns $default instead of throwing.
     *
     * @param  bool  $forceRefresh  If TRUE, cache is forced refreshed
     * @param  int  $hashLength  Required length of hash to return
     * @param  mixed|null  $default  Default value to return if no hash available
     * @return string|null Current heads' hash
     *
     * @throws InvalidArgumentException
     */
    public static function getCurrentHeadHash(
        bool $forceRefresh = false,
        int $hashLength = 7,
        mixed $default = null
    ): ?string {
        if (! $forceRefresh && Cache::has(static::CACHE_KEY)) {
            return Cache::get(static::CACHE_KEY);
        }

        $gitDir = (string) config('application-versioning.git_path');
        $head = static::read($gitDir, 'HEAD');

        if ($head === null) {
            return $default;
        }

        $hash = str_starts_with($head, 'ref:')
            ? static::resolveRef($gitDir, trim(substr($head, 4)))
            : $head;

        if ($hash === null || preg_match('/^[0-9a-f]{40,64}$/', $hash) !== 1) {
            return $default;
        }

        $currentHash = substr($hash, 0, $hashLength);
        Cache::set(static::CACHE_KEY, $currentHash, now()->addSeconds(static::CACHE_TTL_SECONDS));

        return $currentHash;
    }

    /**
     * A loose ref file first, then packed-refs, where `git gc` and fresh
     * clones keep refs.
     */
    protected static function resolveRef(string $gitDir, string $ref): ?string
    {
        $loose = static::read($gitDir, $ref);

        if ($loose !== null) {
            return $loose;
        }

        foreach (explode("\n", (string) static::read($gitDir, 'packed-refs')) as $line) {
            $parts = explode(' ', trim($line));

            if (count($parts) === 2 && $parts[1] === $ref) {
                return $parts[0];
            }
        }

        return null;
    }

    protected static function read(string $gitDir, string $relativePath): ?string
    {
        $path = $gitDir.'/'.$relativePath;

        if ($gitDir === '' || str_contains($relativePath, '..') || ! is_file($path) || ! is_readable($path)) {
            return null;
        }

        $content = file_get_contents($path);

        return $content === false ? null : trim($content);
    }
}
