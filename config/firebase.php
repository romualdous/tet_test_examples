<?php

return [
    // API keys (server key) for each instance
    'api_key' => [
        'customer' => env('FIREBASE_API_KEY_CUSTOMER'),
        'listener' => env('FIREBASE_API_KEY_LISTENER')
    ],
    // Legacy message endpoint
    'url' => env('FIREBASE_CM_URL', 'https://fcm.googleapis.com/fcm/send')
];
