<?php

return [
    'database' => [
        'host' => 'localhost',
        'port' => 3306,
        'dbname' => 'wholeness',
        'charset' => 'utf8mb4',
    ],

    'services' => [
        'client_id' => $_ENV['GOOGLE_CLIENT_ID'],
        'client_secret' => $_ENV['GOOGLE_CLIENT_SECRET'],
        'redirect_uri' => $_ENV['REDIRECT_URI'],
        'apiUrl' => $_ENV['EMAIL_API_URL'],
        'bearerToken' => $_ENV['EMAIL_BEARER_TOKEN'],
    ]
];
