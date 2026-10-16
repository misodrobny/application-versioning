<?php

return [
    /*
     * Where version.yaml lives when no disk is set: an absolute path on the
     * local filesystem.
     */
    'version_file_path' => base_path('version.yaml'),

    /*
     * A filesystem disk from config/filesystems.php to read and write
     * version.yaml through instead, e.g. "s3" when several servers share it.
     * Null keeps the local file at version_file_path.
     */
    'disk' => env('APP_VERSIONING_DISK'),

    /*
     * The path of version.yaml on that disk. Used only when a disk is set.
     */
    'disk_path' => env('APP_VERSIONING_DISK_PATH', 'version.yaml'),

    'git_path' => env('APP_VERSIONING_GIT_PATH', base_path().'/.git'),
];
