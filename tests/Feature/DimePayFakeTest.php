<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Osoobe\DimePay\Data\Orders\CreateOrderData;
use Osoobe\DimePay\Data\Orders\CreateOrderResponseData;
use Osoobe\DimePay\Data\Payments\DirectPaymentData;
use Osoobe\DimePay\Data\Payments\PaymentParamsData;
use Osoobe\DimePay\Data\Payments\PaymentResponseData;
use Osoobe\DimePay\Facades\DimePay;

function makeFullOrderData(): CreateOrderData
{
    return new CreateOrderData(
        id: 'ORDER-001',
        total: 5000,
        subtotal: 5000,
        currency: 'JMD',
        email: 'test@example.com',
        ipAddress: '127.0.0.1',
        referenceTransactionId: 'REF-001',
        items: [
            ['id' => 'item-1', 'name' => 'Test', 'price' => 5000, 'quantity' => 1, 'sku' => 'TEST-001'],
        ],
        taxes: [],
    );
}

function makeFullDirectPaymentData(): DirectPaymentData
{
    return new DirectPaymentData(
        id: 'ORDER-001',
        total: 5000,
        subtotal: 5000,
        currency: 'JMD',
        email: 'test@example.com',
        ipAddress: '127.0.0.1',
        referenceTransactionId: 'REF-001',
        paymentParams: new PaymentParamsData(source: 'TOKEN', token: 'card_test123'),
        items: [
            ['id' => 'item-1', 'name' => 'Test', 'price' => 5000, 'quantity' => 1, 'sku' => 'TEST-001'],
        ],
        taxes: [],
    );
}

it('can fake order creation using Http::fake', function () {
    Http::fake([
        '*/orders' => Http::response([
            'order_url' => 'https://sandbox.dimepay.app/e-order/fake123',
        ], 201),
    ]);

    $response = DimePay::orders()->create(makeFullOrderData());

    expect($response)->toBeInstanceOf(CreateOrderResponseData::class);
    expect($response->orderUrl)->toBe('https://sandbox.dimepay.app/e-order/fake123');
});

it('can fake a sale payment using Http::fake', function () {
    Http::fake([
        '*/payments/sale' => Http::response([
            'id' => 'txn_fake123',
            'amount' => 5000,
            'finalAmount' => 5000,
            'consumerFee' => 0,
            'currency' => 'JMD',
            'status' => 'COMPLETE',
            'source' => 'CARD',
            'refunded' => false,
            'settled' => true,
        ], 200),
    ]);

    $response = DimePay::payments()->sale(makeFullDirectPaymentData());

    expect($response)->toBeInstanceOf(PaymentResponseData::class);
    expect($response->status)->toBe('COMPLETE');
});

it('no real http calls are made when Http::fake is used', function () {
    Http::fake([
        '*' => Http::response(['order_url' => 'https://sandbox.dimepay.app/e-order/fake'], 201),
    ]);

    DimePay::orders()->create(makeFullOrderData());

    Http::assertSentCount(1);
});
