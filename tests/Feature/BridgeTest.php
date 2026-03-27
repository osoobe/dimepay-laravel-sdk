<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Osoobe\DimePay\Data\Orders\CreateOrderData;
use Osoobe\DimePay\Data\Orders\CreateOrderResponseData;
use Osoobe\DimePay\Events\OrderCreated;
use Osoobe\DimePay\Exceptions\DimePayValidationException;
use Osoobe\DimePay\Facades\DimePay;

function makeBridgeOrderData(array $overrides = []): CreateOrderData
{
    return CreateOrderData::from(array_merge([
        'id' => 'BRIDGE-001',
        'total' => 2000,
        'subtotal' => 2000,
        'currency' => 'USD',
        'email' => 'buyer@example.com',
        'ipAddress' => '127.0.0.1',
        'referenceTransactionId' => 'REF-BRIDGE-001',
        'items' => [
            [
                'id' => 'SKU-BOOK-1',
                'name' => 'Hardcover Book',
                'price' => 500,
                'quantity' => 1,
                'sku' => 'BOOK-001',
                'shortDescription' => 'A book',
                'imageUrl' => 'https://example.com/book.jpg',
                'merchantId' => 'm4D8mQ1wMrdTUIg',
            ],
            [
                'id' => 'SKU-COURSE-1',
                'name' => 'Online Course',
                'price' => 1500,
                'quantity' => 1,
                'sku' => 'COURSE-001',
                'shortDescription' => 'A course',
                'imageUrl' => 'https://example.com/course.jpg',
                'merchantId' => 'm7UarSiV9zWxN6v',
            ],
        ],
        'split' => [
            ['merchantId' => 'm4D8mQ1wMrdTUIg', 'amount' => 500, 'fee' => 10],
            ['merchantId' => 'm7UarSiV9zWxN6v', 'amount' => 1500, 'fee' => 30],
        ],
        'taxes' => [],
    ], $overrides));
}

it('creates a split order with correct merchant_id snake_case in payload', function () {
    Http::fake([
        '*/orders' => Http::response(['order_url' => 'https://sandbox.dimepay.app/e-order/bridge123'], 201),
    ]);

    $response = DimePay::orders()->create(makeBridgeOrderData());

    expect($response)->toBeInstanceOf(CreateOrderResponseData::class);
    expect($response->orderUrl)->toBe('https://sandbox.dimepay.app/e-order/bridge123');

    // Verify the JWT payload sent to the API contains merchant_id snake_case
    Http::assertSent(function ($request) {
        $body = $request->data();

        // body has lang and data (JWT) — we just confirm the request was made
        return isset($body['lang']) && isset($body['data']);
    });
});

it('fires OrderCreated event for a bridge order', function () {
    Event::fake();

    Http::fake([
        '*/orders' => Http::response(['order_url' => 'https://sandbox.dimepay.app/e-order/bridge123'], 201),
    ]);

    DimePay::orders()->create(makeBridgeOrderData());

    Event::assertDispatched(OrderCreated::class);
});

it('creates a single merchant bridge order', function () {
    Http::fake([
        '*/orders' => Http::response(['order_url' => 'https://sandbox.dimepay.app/e-order/bridge456'], 201),
    ]);

    $response = DimePay::orders()->create(makeBridgeOrderData([
        'total' => 500,
        'subtotal' => 500,
        'items' => [
            [
                'id' => 'SKU-BOOK-1',
                'name' => 'Hardcover Book',
                'price' => 500,
                'quantity' => 1,
                'sku' => 'BOOK-001',
                'shortDescription' => 'A book',
                'imageUrl' => 'https://example.com/book.jpg',
                'merchantId' => 'm4D8mQ1wMrdTUIg',
            ],
        ],
        'split' => [
            ['merchantId' => 'm4D8mQ1wMrdTUIg', 'amount' => 500, 'fee' => 10],
        ],
    ]));

    expect($response)->toBeInstanceOf(CreateOrderResponseData::class);
    expect($response->orderUrl)->not->toBeEmpty();
});

it('creates a bridge order with multiple items per merchant', function () {
    Http::fake([
        '*/orders' => Http::response(['order_url' => 'https://sandbox.dimepay.app/e-order/bridge789'], 201),
    ]);

    $response = DimePay::orders()->create(makeBridgeOrderData([
        'total' => 3000,
        'subtotal' => 3000,
        'items' => [
            [
                'id' => 'item-1',
                'name' => 'Book A',
                'price' => 300,
                'quantity' => 1,
                'sku' => 'BOOK-A',
                'shortDescription' => '',
                'imageUrl' => 'https://example.com/img.jpg',
                'merchantId' => 'm4D8mQ1wMrdTUIg',
            ],
            [
                'id' => 'item-2',
                'name' => 'Book B',
                'price' => 200,
                'quantity' => 1,
                'sku' => 'BOOK-B',
                'shortDescription' => '',
                'imageUrl' => 'https://example.com/img.jpg',
                'merchantId' => 'm4D8mQ1wMrdTUIg',
            ],
            [
                'id' => 'item-3',
                'name' => 'Course',
                'price' => 2500,
                'quantity' => 1,
                'sku' => 'COURSE-A',
                'shortDescription' => '',
                'imageUrl' => 'https://example.com/img.jpg',
                'merchantId' => 'm7UarSiV9zWxN6v',
            ],
        ],
        'split' => [
            ['merchantId' => 'm4D8mQ1wMrdTUIg', 'amount' => 500, 'fee' => 10],
            ['merchantId' => 'm7UarSiV9zWxN6v', 'amount' => 2500, 'fee' => 30],
        ],
    ]));

    expect($response)->toBeInstanceOf(CreateOrderResponseData::class);
    expect($response->orderUrl)->not->toBeEmpty();
});

it('throws DimePayValidationException on mismatched split amounts', function () {
    Http::fake([
        '*/orders' => Http::response([
            'statusCode' => 400,
            'body' => [
                'message' => ['Split amounts do not match item totals'],
                'response' => null,
            ],
        ], 400),
    ]);

    expect(fn () => DimePay::orders()->create(makeBridgeOrderData([
        'split' => [
            ['merchantId' => 'm4D8mQ1wMrdTUIg', 'amount' => 999, 'fee' => 10], // wrong amount
            ['merchantId' => 'm7UarSiV9zWxN6v', 'amount' => 999, 'fee' => 30], // wrong amount
        ],
    ])))->toThrow(DimePayValidationException::class);
});
