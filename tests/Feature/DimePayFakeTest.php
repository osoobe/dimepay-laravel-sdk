<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Osoobe\DimePay\Data\Orders\CreateOrderData;
use Osoobe\DimePay\Data\Orders\OrderResponseData;
use Osoobe\DimePay\Data\Payments\DirectPaymentData;
use Osoobe\DimePay\Data\Payments\PaymentParamsData;
use Osoobe\DimePay\Data\Payments\PaymentResponseData;
use Osoobe\DimePay\Facades\DimePay;

it('can fake order creation using Http::fake', function () {
    Http::fake([
        '*/orders' => Http::response([
            'id' => 'ORDER-001',
            'token' => 'fake-token-123',
            'status' => 'PENDING',
            'currency' => 'JMD',
            'total' => 5000,
            'subtotal' => 5000,
        ], 201),
    ]);

    $response = DimePay::orders()->create(new CreateOrderData(
        id: 'ORDER-001',
        total: 5000,
        subtotal: 5000,
        currency: 'JMD',
        email: 'test@example.com',
    ));

    expect($response)->toBeInstanceOf(OrderResponseData::class);
    expect($response->token)->toBe('fake-token-123');
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

    $response = DimePay::payments()->sale(new DirectPaymentData(
        id: 'ORDER-001',
        total: 5000,
        subtotal: 5000,
        currency: 'JMD',
        email: 'test@example.com',
        paymentParams: new PaymentParamsData(
            source: 'TOKEN',
            token: 'card_test123',
        ),
    ));

    expect($response)->toBeInstanceOf(PaymentResponseData::class);
    expect($response->status)->toBe('COMPLETE');
    expect($response->id)->toBe('txn_fake123');
});

it('no real http calls are made when Http::fake is used', function () {
    Http::fake([
        '*' => Http::response(['id' => 'ORDER-001', 'token' => 'fake', 'status' => 'PENDING', 'currency' => 'JMD', 'total' => 100, 'subtotal' => 100], 200),
    ]);

    DimePay::orders()->create(new CreateOrderData(
        id: 'ORDER-001',
        total: 100,
        subtotal: 100,
        currency: 'JMD',
        email: 'test@example.com',
    ));

    Http::assertSentCount(1);
});
