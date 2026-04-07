<?php

return [
    'ttl' => 3600, // Cache duration in seconds
    'storage' => 'cache', // 'cache' or 'redis'
    'duplicate_response' => 409, // HTTP status for duplicates in block mode
    'enable_logging' => env('APP_DEBUG', false), // Logs duplicate requests if true
    'mode' => 'replay', // 'replay' to return cached response, 'block' to block duplicates
];