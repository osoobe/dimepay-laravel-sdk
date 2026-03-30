<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    |
    | Determines which base URL is used for API requests.
    | Supported: "sandbox", "production"
    |
    */
    'environment' => env('DIMEPAY_ENV', 'sandbox'),

    /*
    |--------------------------------------------------------------------------
    | API Credentials
    |--------------------------------------------------------------------------
    |
    | client_key — sent as the `client_key` header on every API request.
    | secret_key — used server-side to sign JWT payloads. Never expose this.
    |
    | Obtain both from your DimePay dashboard → Developer section.
    |
    */
    'client_key' => env('DIMEPAY_CLIENT_KEY'),
    'secret_key' => env('DIMEPAY_SECRET_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Base URLs
    |--------------------------------------------------------------------------
    |
    | The SDK automatically resolves the correct URL based on `environment`.
    | Override here if you're behind a proxy or DimePay changes endpoints.
    |
    */
    'base_urls' => [
        'production' => env('DIMEPAY_PRODUCTION_URL', 'https://api.dimepay.app/dapi/v1'),
        'sandbox' => env('DIMEPAY_SANDBOX_URL', 'https://sandbox.api.dimepay.app/dapi/v1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Options
    |--------------------------------------------------------------------------
    |
    | timeout     — seconds before a request is aborted.
    | retries     — number of automatic retries on connection failure.
    | retry_delay — milliseconds to wait between retries.
    |
    */
    'timeout' => (int) env('DIMEPAY_TIMEOUT', 30),
    'retries' => (int) env('DIMEPAY_RETRIES', 2),
    'retry_delay' => (int) env('DIMEPAY_RETRY_DELAY', 500),

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | When enabled, all outgoing requests and incoming responses are logged.
    | Set `level` to "debug", "info", "warning", or "error".
    |
    */
    'logging' => [
        'enabled' => (bool) env('DIMEPAY_LOGGING', true),
        'channel' => env('DIMEPAY_LOG_CHANNEL', 'stack'),
        'level' => env('DIMEPAY_LOG_LEVEL', 'debug'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    |
    | Controls whether the package registers its built-in routes.
    | Built-in routes handle webhooks and payment callbacks.
    |
    | prefix     — URL prefix for all package routes.
    | middleware — middleware applied to all package routes.
    |
    */
    'routes' => [
        'enabled' => (bool) env('DIMEPAY_ROUTES_ENABLED', true),
        'prefix' => env('DIMEPAY_ROUTES_PREFIX', 'dimepay'),
        'middleware' => ['api'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook
    |--------------------------------------------------------------------------
    |
    | secret    — used to verify incoming webhook signatures from DimePay.
    | tolerance — replay-attack window in seconds. Requests older than this
    |             value will be rejected (when signature verification is active).
    |
    */
    'webhook' => [
        'secret' => env('DIMEPAY_WEBHOOK_SECRET'),
        'tolerance' => (int) env('DIMEPAY_WEBHOOK_TOLERANCE', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | JWT Signing
    |--------------------------------------------------------------------------
    |
    | algorithm — HMAC algorithm used to sign order and card payloads.
    | ttl       — token time-to-live in seconds.
    |
    */
    'jwt' => [
        'algorithm' => env('DIMEPAY_JWT_ALGORITHM', 'HS256'),
        'ttl' => (int) env('DIMEPAY_JWT_TTL', 3600),
    ],

];
