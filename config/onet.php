<?php
return [
    // Base URL Interest Profiler
    'base_url' => env('ONET_BASE_URL', 'https://services.onetcenter.org/ws/mnm/interestprofiler'),

    // Basic Auth: fallback ke ONET_USER atau ONET_API_USERNAME
    'user'   => env('ONET_USER', env('ONET_API_USERNAME')),
    'pass'   => env('ONET_PASS', env('ONET_API_PASSWORD')),

    // Client param
    'client' => env('ONET_CLIENT', env('ONET_API_USERNAME')),
];
