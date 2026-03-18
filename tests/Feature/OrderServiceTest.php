<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Osoobe\DimePay\Data\Orders\CreateOrderData;
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
        webhookUrl: 'https://example.com/webhook',
        redirectUrl: 'https://example.com/callback',
    );
}

function orderResponse(): array
{
    return [
        'id' => 'ORDER-001',
        'token' => 'order_abc123',
        'status' => 'PENDING',
        'currency' => 'JMD',
        'total' => 5000,
        'subtotal' => 5000,
    ];
}

it('creates an order and returns OrderResponseData', function () {
    Http::fake([
        '*/orders' => Http::response(orderResponse(), 201),
    ]);

    $response = DimePay::orders()->create(makeOrderData());

    expect($response)->toBeInstanceOf(OrderResponseData::class);
    expect($response->token)->toBe('order_abc123');
    expect($response->status)->toBe('PENDING');
});

it('fires OrderCreated event on successful create', function () {
    Event::fake();

    Http::fake([
        '*/orders' => Http::response(orderResponse(), 201),
    ]);

    DimePay::orders()->create(makeOrderData());

    Event::assertDispatched(OrderCreated::class);
});

it('finds an order by token and returns OrderResponseData', function () {
    Http::fake([
        '*/orders/*' => Http::response(orderResponse(), 200),
    ]);

    $response = DimePay::orders()->find('order_abc123');

    expect($response)->toBeInstanceOf(OrderResponseData::class);
    expect($response->id)->toBe('ORDER-001');
});

it('throws DimePayAuthException on 401 when creating order', function () {
    Http::fake([
        '*/orders' => Http::response(['code' => 'unauthorized', 'message' => 'Invalid key', 'details' => []], 401),
    ]);

    expect(fn () => DimePay::orders()->create(makeOrderData()))
        ->toThrow(DimePayAuthException::class);
});

it('throws DimePayNotFoundException on 404 when finding order', function () {
    Http::fake([
        '*/orders/*' => Http::response(['code' => 'not_found', 'message' => 'Not found', 'details' => []], 404),
    ]);

    expect(fn () => DimePay::orders()->find('bad-token'))
        ->toThrow(DimePayNotFoundException::class);
});
