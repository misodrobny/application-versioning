<?php

return [
    'version_file_path' => base_path('version.yaml'),
    'git_path' => env('APP_VERSIONING_GIT_PATH', base_path().'/.git'),
];
