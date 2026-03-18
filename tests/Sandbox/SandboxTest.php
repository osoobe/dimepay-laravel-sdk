<?php

declare(strict_types=1);

/**
 * Sandbox Integration Tests
 *
 * These tests hit the real DimePay sandbox API.
 * They are excluded from CI and only run manually.
 *
 * Usage:
 *   DIMEPAY_CLIENT_KEY=ck_xxx DIMEPAY_SECRET_KEY=sk_xxx vendor/bin/pest tests/Sandbox --group sandbox
 */

 use Osoobe\DimePay\Data\Orders\CreateOrderData;
 use Osoobe\DimePay\Data\Orders\CreateOrderResponseData;
 use Osoobe\DimePay\Data\Orders\OrderItemData;
 use Osoobe\DimePay\Data\Payments\HostedPageResponseData;
 use Osoobe\DimePay\Data\Shared\TaxData;
 use Osoobe\DimePay\Exceptions\DimePayException;
 use Osoobe\DimePay\Http\DimePayClient;
 use Osoobe\DimePay\Services\OrderService;
 use Osoobe\DimePay\Services\PaymentService;
 use Osoobe\DimePay\Tests\TestCase;
 use Spatie\LaravelData\DataCollection;

uses(TestCase::class)->group('sandbox');

function sandboxApiConfig(): array
{
    return [
        'environment' => 'sandbox',
        'client_key'  => (string) getenv('DIMEPAY_CLIENT_KEY'),
        'secret_key'  => (string) getenv('DIMEPAY_SECRET_KEY'),
        'base_urls'   => [
            'sandbox'    => 'https://sandbox.api.dimepay.app/dapi/v1',
            'production' => 'https://api.dimepay.app/dapi/v1',
        ],
        'timeout'     => 30,
        'retries'     => 1,
        'retry_delay' => 500,
        'logging'     => ['enabled' => false, 'channel' => 'stack', 'level' => 'debug'],
        'webhook'     => ['secret' => null, 'tolerance' => 300],
        'jwt'         => ['algorithm' => 'HS256', 'ttl' => 3600],
    ];
}
function sandboxApiClient(): DimePayClient
{
    return new DimePayClient(sandboxApiConfig());
}

function sandboxOrderData(): CreateOrderData
{
    return new CreateOrderData(
        id: 'TEST-' . uniqid(),
        total: 5000,
        subtotal: 5000,
        currency: 'JMD',
        email: 'test@example.com',
        ipAddress: '127.0.0.1',
        referenceTransactionId: 'REF-' . uniqid(),
        webhookUrl: 'https://example.com/webhook',
        redirectUrl: 'https://example.com/callback',
        checkoutUrl: 'https://example.com/checkout',
        orderComments: 'Test order',
        items: OrderItemData::collect([
            [
                'id'       => 'item-1',
                'name'     => 'Test Item',
                'price'    => 5000,
                'quantity' => 1,
                'sku'      => 'TEST-001',
            ],
        ], DataCollection::class),
        taxes: TaxData::collect([], DataCollection::class),
    );
}

it('can create an order against sandbox', function () {
    try {
        $response = (new OrderService(sandboxApiClient()))->create(sandboxOrderData());

        expect($response)->toBeInstanceOf(CreateOrderResponseData::class);
        expect($response->orderUrl)->not->toBeEmpty();

        dump(['order_url' => $response->orderUrl]);
    } catch (DimePayException $e) {
        dump([
            'status'  => $e->getStatus(),
            'code'    => $e->getErrorCode(),
            'message' => $e->getMessage(),
            'details' => $e->getDetails(),
        ]);
        throw $e;
    }
});

it('can create a hosted payment page against sandbox', function () {
    try {
        $response = (new PaymentService(sandboxApiClient()))->hostedPage(sandboxOrderData());

        expect($response)->toBeInstanceOf(HostedPageResponseData::class);
        expect($response->orderUrl)->not->toBeEmpty();

        dump(['order_url' => $response->orderUrl]);
    } catch (DimePayException $e) {
        dump([
            'status'  => $e->getStatus(),
            'code'    => $e->getErrorCode(),
            'message' => $e->getMessage(),
            'details' => $e->getDetails(),
        ]);
        throw $e;
    }
});

it('dumps raw successful response', function () {
    $config = sandboxApiConfig();

    $signer = new \Osoobe\DimePay\Support\JwtSigner($config);

    $response = \Illuminate\Support\Facades\Http::withHeaders([
        'client_key'   => $config['client_key'],
        'Accept'       => 'application/json',
        'Content-Type' => 'application/json',
    ])->post('https://sandbox.api.dimepay.app/dapi/v1/orders', [
        'lang' => 'en',
        'data' => $signer->sign(sandboxOrderData()->toArray()),
    ]);

    dump([
        'status' => $response->status(),
        'body'   => $response->body(),
        'json'   => $response->json(),
    ]);

    expect(true)->toBeTrue();
});
