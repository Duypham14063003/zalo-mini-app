<?php

return [
    'runtime_base_url' => env('MINI_APP_RUNTIME_BASE_URL', env('APP_URL', 'http://localhost')),
    'runtime_path_template' => env('MINI_APP_RUNTIME_PATH_TEMPLATE', '/play/{public_id}'),
    'zalo_launch_url_template' => env('ZALO_MINI_APP_LAUNCH_URL_TEMPLATE'),
];
