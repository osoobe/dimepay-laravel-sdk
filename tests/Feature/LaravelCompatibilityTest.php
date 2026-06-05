<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Osoobe\DimePay\Data\Orders\CreateOrderData;
use Osoobe\DimePay\Data\Orders\CreateOrderResponseData;
use Osoobe\DimePay\Data\Payments\DirectPaymentData;
use Osoobe\DimePay\Data\Payments\PaymentParamsData;
use Osoobe\DimePay\Data\Payments\PaymentResponseData;
use Osoobe\DimePay\Events\OrderCreated;
use Osoobe\DimePay\Facades\DimePay;
use Osoobe\DimePay\Services\CardService;
use Osoobe\DimePay\Services\OrderService;
use Osoobe\DimePay\Services\PaymentService;
use Osoobe\DimePay\Support\JwtSigner;

it('runs on a supported Laravel version', function () {
    $version = (int) Application::VERSION;

    expect($version)->toBeGreaterThanOrEqual(10);
});

it('resolves the DimePay facade on the current Laravel version', function () {
    expect(DimePay::orders())->toBeInstanceOf(OrderService::class);
    expect(DimePay::payments())->toBeInstanceOf(PaymentService::class);
    expect(DimePay::cards())->toBeInstanceOf(CardService::class);
    expect(DimePay::jwt())->toBeInstanceOf(JwtSigner::class);
});

it('binds config correctly on the current Laravel version', function () {
    $config = config('dimepay');

    expect($config)->toBeArray();
    expect($config['environment'])->toBe('sandbox');
    expect($config['client_key'])->not->toBeEmpty();
    expect($config['secret_key'])->not->toBeEmpty();
});

it('creates an order on the current Laravel version', function () {
    Http::fake([
        '*/orders' => Http::response(['order_url' => 'https://sandbox.dimepay.app/e-order/compat-test'], 201),
    ]);

    $data = CreateOrderData::from([
        'id' => 'COMPAT-001',
        'total' => 1000,
        'subtotal' => 1000,
        'currency' => 'USD',
        'email' => 'compat@example.com',
        'ipAddress' => '127.0.0.1',
        'referenceTransactionId' => 'REF-COMPAT-001',
        'items' => [[
            'id' => 'SKU-001',
            'name' => 'Test Item',
            'price' => 1000,
            'quantity' => 1,
            'sku' => 'TEST-001',
            'shortDescription' => 'Compatibility test item',
            'imageUrl' => 'https://example.com/img.jpg',
            'merchantId' => 'm4D8mQ1wMrdTUIg',
        ]],
        'split' => [
            ['merchantId' => 'm4D8mQ1wMrdTUIg', 'amount' => 1000, 'fee' => 10],
        ],
        'taxes' => [],
    ]);

    $response = DimePay::orders()->create($data);

    expect($response)->toBeInstanceOf(CreateOrderResponseData::class);
    expect($response->orderUrl)->toBe('https://sandbox.dimepay.app/e-order/compat-test');
});

it('dispatches events via the Laravel event system on the current version', function () {
    Event::fake();

    Http::fake([
        '*/orders' => Http::response(['order_url' => 'https://sandbox.dimepay.app/e-order/event-compat'], 201),
    ]);

    $data = CreateOrderData::from([
        'id' => 'EVENT-COMPAT-001',
        'total' => 500,
        'subtotal' => 500,
        'currency' => 'USD',
        'email' => 'events@example.com',
        'ipAddress' => '127.0.0.1',
        'referenceTransactionId' => 'REF-EVENT-001',
        'items' => [[
            'id' => 'SKU-EVENT',
            'name' => 'Event Item',
            'price' => 500,
            'quantity' => 1,
            'sku' => 'EVT-001',
            'shortDescription' => 'Event test item',
            'imageUrl' => 'https://example.com/img.jpg',
            'merchantId' => 'm4D8mQ1wMrdTUIg',
        ]],
        'split' => [
            ['merchantId' => 'm4D8mQ1wMrdTUIg', 'amount' => 500, 'fee' => 5],
        ],
        'taxes' => [],
    ]);

    DimePay::orders()->create($data);

    Event::assertDispatched(OrderCreated::class);
});

it('handles Http facade faking correctly on the current Laravel version', function () {
    Http::fake([
        '*/payments/*' => Http::response([
            'id' => 'TXN-COMPAT-001',
            'status' => 'authorized',
            'amount' => 2000,
            'final_amount' => 2000,
            'consumer_fee' => 0,
            'currency' => 'USD',
            'source' => 'card',
        ], 200),
    ]);

    $data = DirectPaymentData::from([
        'id' => 'ORDER-COMPAT-001',
        'total' => 2000,
        'subtotal' => 2000,
        'currency' => 'USD',
        'email' => 'compat@example.com',
        'ipAddress' => '127.0.0.1',
        'referenceTransactionId' => 'REF-COMPAT-PAY',
        'paymentParams' => PaymentParamsData::from(['token' => 'tok_test_compat']),
    ]);

    $response = DimePay::payments()->authorize($data);

    expect($response)->toBeInstanceOf(PaymentResponseData::class);
    expect($response->id)->toBe('TXN-COMPAT-001');
    expect($response->status)->toBe('authorized');

    Http::assertSentCount(1);
});
