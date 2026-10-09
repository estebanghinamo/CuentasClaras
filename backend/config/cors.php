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

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // 'https://localhost' es el origen FIJO que usa el WebView de Capacitor en
    // Android/iOS (independiente de qué dominio apunte environment.ts/apiUrl) -
    // sin esto, la app nativa queda bloqueada por CORS aunque el request llegue
    // bien al backend (se ve en los logs, pero el cliente nunca ve la respuesta).
    // FRONTEND_URL es el único valor que cambia entre dev/producción.
    'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:4200'), 'https://localhost'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
