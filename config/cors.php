<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // ⚠️ PENTING: Tambahkan URL frontend Next.js Anda di sini
    'allowed_origins' => ['http://localhost:3000'], 

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // Set true jika menggunakan cookie-based auth, false jika murni Bearer Token
    'supports_credentials' => true, 
];