<?php

return [
    'elytica_service' => [
        'client_id' => env('ELYTICA_SERVICE_CLIENT_ID', '1'),
        'client_secret' => env('ELYTICA_SERVICE_CLIENT_SECRET', 'xxx'),
        'redirect' => env('ELYTICA_SERVICE_REDIRECT_URI', 'http://localhost'),
    ],
];
