<?php

return [
    'elytica_service' => [
        'client_id'     => env('ELYTICA_SERVICE_CLIENT_ID'),
        'client_secret' => env('ELYTICA_SERVICE_CLIENT_SECRET'),
        'redirect'      => env('ELYTICA_SERVICE_REDIRECT_URI', 'http://localhost'),
        'base_url'      => env('ELYTICA_SERVICE_BASE_URL', 'https://service.elytica.com'),
    ],
];
