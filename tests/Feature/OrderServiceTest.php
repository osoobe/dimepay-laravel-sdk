<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Osoobe\DimePay\Data\Orders\CreateOrderData;
use Osoobe\DimePay\Data\Orders\CreateOrderResponseData;
use Osoobe\DimePay\Data\Orders\OrderResponseData;
use Osoobe\DimePay\Events\OrderCreated;
use Osoobe\DimePay\Exceptions\DimePayAuthException;
use Osoobe\DimePay\Exceptions\DimePayNotFoundException;
use Osoobe\DimePay\Facades\DimePay;

function makeOrderData(): CreateOrderData
{
    return new CreateOrderData(
        id: 'ORDER-001',
        total: 5000,
        subtotal: 5000,
        currency: 'JMD',
        email: 'customer@example.com',
        ipAddress: '127.0.0.1',
        referenceTransactionId: 'REF-001',
        webhookUrl: 'https://example.com/webhook',
        redirectUrl: 'https://example.com/callback',
        items: [
            ['id' => 'item-1', 'name' => 'iMac', 'price' => 5000, 'quantity' => 1, 'sku' => 'IMAC-001'],
        ],
        taxes: [],
    );
}

it('creates an order and returns CreateOrderResponseData with order_url', function () {
    Http::fake([
        '*/orders' => Http::response(['order_url' => 'https://sandbox.dimepay.app/e-order/abc123'], 201),
    ]);

    $response = DimePay::orders()->create(makeOrderData());

    expect($response)->toBeInstanceOf(CreateOrderResponseData::class);
    expect($response->orderUrl)->toBe('https://sandbox.dimepay.app/e-order/abc123');
});

it('fires OrderCreated event on successful create', function () {
    Event::fake();

    Http::fake([
        '*/orders' => Http::response(['order_url' => 'https://sandbox.dimepay.app/e-order/abc123'], 201),
    ]);

    DimePay::orders()->create(makeOrderData());

    Event::assertDispatched(OrderCreated::class);
});

it('finds an order by token and returns OrderResponseData', function () {
    Http::fake([
        '*/orders/*' => Http::response([
            'id' => 'ORDER-001',
            'token' => 'order_abc123',
            'status' => 'COMPLETE',
            'currency' => 'JMD',
            'total' => 5000,
            'subtotal' => 5000,
        ], 200),
    ]);

    $response = DimePay::orders()->find('order_abc123');

    expect($response)->toBeInstanceOf(OrderResponseData::class);
    expect($response->id)->toBe('ORDER-001');
});

it('throws DimePayAuthException on 401 when creating order', function () {
    Http::fake([
        '*/orders' => Http::response(['statusCode' => 401, 'body' => ['message' => 'Unauthorized', 'response' => null]], 401),
    ]);

    expect(fn () => DimePay::orders()->create(makeOrderData()))
        ->toThrow(DimePayAuthException::class);
});

it('throws DimePayNotFoundException on 404 when finding order', function () {
    Http::fake([
        '*/orders/*' => Http::response(['statusCode' => 404, 'body' => ['message' => 'Not found', 'response' => null]], 404),
    ]);

    expect(fn () => DimePay::orders()->find('bad-token'))
        ->toThrow(DimePayNotFoundException::class);
});
