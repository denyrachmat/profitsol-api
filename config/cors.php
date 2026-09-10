<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout', 'user'],

    // 'allowed_methods' => ['*'],

    'allowed_origins' => [
        'https://intranet.sumitronics-indonesia.com',
        'https://portal.sumitronics-indonesia.com',
        'http://localhost:8080',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'], // this is the line i needed to update to solve the issue.

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    // 'allowed_origins' => [
    //     'https://portal.sumitronics-indonesia.com',
    //     'https://intranet.sumitronics-indonesia.com',
    //     'https://api.sumitronics-indonesia.com',
    //     'http://localhost',
    //     'http://127.0.0.1:9000',
    //     'http://localhost:8080',
    //     'http://stx-api.test',
    // ],
    // 'allowed_headers' => ['X-Requested-With', 'Content-Type', 'X-XSRF-TOKEN', 'Authorization'],

    'exposed_headers' => [],

    'max_age' => 86400,

    'supports_credentials' => true,

];
