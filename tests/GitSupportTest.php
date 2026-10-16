<?php

use DrobnyDev\ApplicationVersioning\Support\GitSupport;

beforeEach(function () {
    $this->gitDir = sys_get_temp_dir().'/av-git-'.bin2hex(random_bytes(4));
    mkdir($this->gitDir.'/refs/heads', 0777, true);
    config(['application-versioning.git_path' => $this->gitDir]);
    $this->sha = 'a48f19c'.str_repeat('0', 33);
});

afterEach(function () {
    exec('rm -rf '.escapeshellarg($this->gitDir));
});

it('reads the hash of a checked out branch', function () {
    file_put_contents($this->gitDir.'/HEAD', "ref: refs/heads/main\n");
    file_put_contents($this->gitDir.'/refs/heads/main', $this->sha."\n");

    expect(GitSupport::getCurrentHeadHash(forceRefresh: true))->toBe('a48f19c');
});

it('finds a ref kept only in packed-refs', function () {
    file_put_contents($this->gitDir.'/HEAD', "ref: refs/heads/main\n");
    file_put_contents($this->gitDir.'/packed-refs', "# pack-refs with: peeled fully-peeled sorted\n{$this->sha} refs/heads/main\n");

    expect(GitSupport::getCurrentHeadHash(forceRefresh: true))->toBe('a48f19c');
});

it('reads a detached HEAD instead of throwing', function () {
    file_put_contents($this->gitDir.'/HEAD', $this->sha."\n");

    expect(GitSupport::getCurrentHeadHash(forceRefresh: true))->toBe('a48f19c');
});

it('returns the default when no hash can be found', function (?callable $prepare) {
    $prepare && $prepare($this->gitDir);

    expect(GitSupport::getCurrentHeadHash(forceRefresh: true, default: 'none'))->toBe('none');
})->with([
    'no HEAD' => [null],
    'a ref that does not exist' => [fn (string $dir) => file_put_contents($dir.'/HEAD', "ref: refs/heads/gone\n")],
    'garbage in HEAD' => [fn (string $dir) => file_put_contents($dir.'/HEAD', "not a hash\n")],
]);

it('returns the default when the git directory is missing', function () {
    config(['application-versioning.git_path' => $this->gitDir.'/missing']);

    expect(GitSupport::getCurrentHeadHash(forceRefresh: true))->toBeNull();
});

it('honours the requested hash length', function () {
    file_put_contents($this->gitDir.'/HEAD', $this->sha."\n");

    expect(GitSupport::getCurrentHeadHash(forceRefresh: true, hashLength: 10))->toBe('a48f19c000');
});
