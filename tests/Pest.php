<?php

declare(strict_types=1);

use Osoobe\DimePay\Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Unit');

function sandboxConfig(array $overrides = []): array
{
    return array_merge([
        'environment' => 'sandbox',
        'client_key' => 'ck_test_dimepay_sdk_unit_tests',
        // Must be at least 32 chars (256 bits) for HS256
        'secret_key' => 'sk_test_dimepay_sdk_unit_tests_secret_key_256bit',
        'base_urls' => [
            'production' => 'https://api.dimepay.app/dapi/v1',
            'sandbox' => 'https://sandbox.api.dimepay.app/dapi/v1',
        ],
        'timeout' => 30,
        'retries' => 2,
        'retry_delay' => 500,
        'logging' => ['enabled' => false, 'channel' => 'stack', 'level' => 'debug'],
        'routes' => ['enabled' => false, 'prefix' => 'dimepay', 'middleware' => ['api']],
        'webhook' => ['secret' => 'test-webhook-secret-key-256bit-long', 'tolerance' => 300],
        'jwt' => ['algorithm' => 'HS256', 'ttl' => 3600],
    ], $overrides);
}
