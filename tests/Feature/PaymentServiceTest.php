<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Osoobe\DimePay\Data\Orders\CreateOrderData;
use Osoobe\DimePay\Data\Payments\DirectPaymentData;
use Osoobe\DimePay\Data\Payments\HostedPageResponseData;
use Osoobe\DimePay\Data\Payments\PaymentParamsData;
use Osoobe\DimePay\Data\Payments\PaymentResponseData;
use Osoobe\DimePay\Events\HostedPaymentPageCreated;
use Osoobe\DimePay\Events\PaymentAuthorized;
use Osoobe\DimePay\Events\PaymentCaptured;
use Osoobe\DimePay\Events\PaymentRefunded;
use Osoobe\DimePay\Events\PaymentSaleProcessed;
use Osoobe\DimePay\Events\PaymentVoided;
use Osoobe\DimePay\Exceptions\DimePayAuthException;
use Osoobe\DimePay\Facades\DimePay;

function makeOrderDataForPayment(): CreateOrderData
{
    return new CreateOrderData(
        id: 'ORDER-001',
        total: 5000,
        subtotal: 5000,
        currency: 'JMD',
        email: 'customer@example.com',
        ipAddress: '127.0.0.1',
        referenceTransactionId: 'REF-001',
        items: [
            ['id' => 'item-1', 'name' => 'Test', 'price' => 5000, 'quantity' => 1, 'sku' => 'TEST-001'],
        ],
        taxes: [],
    );
}

function makeDirectPaymentData(): DirectPaymentData
{
    return new DirectPaymentData(
        id: 'ORDER-001',
        total: 5000,
        subtotal: 5000,
        currency: 'JMD',
        email: 'customer@example.com',
        ipAddress: '127.0.0.1',
        referenceTransactionId: 'REF-001',
        paymentParams: new PaymentParamsData(
            source: 'TOKEN',
            token: 'card_abc123',
        ),
        items: [
            ['id' => 'item-1', 'name' => 'Test', 'price' => 5000, 'quantity' => 1, 'sku' => 'TEST-001'],
        ],
        taxes: [],
    );
}

function paymentResponse(): array
{
    return [
        'id'          => 'txn_abc123',
        'amount'      => 5000,
        'finalAmount' => 5000,
        'consumerFee' => 0,
        'currency'    => 'JMD',
        'status'      => 'COMPLETE',
        'source'      => 'CARD',
        'refunded'    => false,
        'settled'     => true,
    ];
}

it('creates a hosted payment page and returns HostedPageResponseData', function () {
    Http::fake(['*/payments/hosted-page' => Http::response(['order_url' => 'https://pay.dimepay.app/test'], 201)]);

    $response = DimePay::payments()->hostedPage(makeOrderDataForPayment());

    expect($response)->toBeInstanceOf(HostedPageResponseData::class);
    expect($response->orderUrl)->toBe('https://pay.dimepay.app/test');
});

it('fires HostedPaymentPageCreated event', function () {
    Event::fake();
    Http::fake(['*/payments/hosted-page' => Http::response(['order_url' => 'https://pay.dimepay.app/test'], 201)]);

    DimePay::payments()->hostedPage(makeOrderDataForPayment());

    Event::assertDispatched(HostedPaymentPageCreated::class);
});

it('authorizes a payment and returns PaymentResponseData', function () {
    Http::fake(['*/payments/auth' => Http::response(paymentResponse(), 200)]);

    $response = DimePay::payments()->authorize(makeDirectPaymentData());

    expect($response)->toBeInstanceOf(PaymentResponseData::class);
    expect($response->id)->toBe('txn_abc123');
});

it('fires PaymentAuthorized event', function () {
    Event::fake();
    Http::fake(['*/payments/auth' => Http::response(paymentResponse(), 200)]);

    DimePay::payments()->authorize(makeDirectPaymentData());

    Event::assertDispatched(PaymentAuthorized::class);
});

it('processes a sale and returns PaymentResponseData', function () {
    Http::fake(['*/payments/sale' => Http::response(paymentResponse(), 200)]);

    $response = DimePay::payments()->sale(makeDirectPaymentData());

    expect($response)->toBeInstanceOf(PaymentResponseData::class);
});

it('fires PaymentSaleProcessed event', function () {
    Event::fake();
    Http::fake(['*/payments/sale' => Http::response(paymentResponse(), 200)]);

    DimePay::payments()->sale(makeDirectPaymentData());

    Event::assertDispatched(PaymentSaleProcessed::class);
});

it('captures a payment', function () {
    Http::fake(['*/payments/capture' => Http::response(paymentResponse(), 200)]);

    $response = DimePay::payments()->capture('txn_abc123');

    expect($response)->toBeInstanceOf(PaymentResponseData::class);
});

it('fires PaymentCaptured event', function () {
    Event::fake();
    Http::fake(['*/payments/capture' => Http::response(paymentResponse(), 200)]);

    DimePay::payments()->capture('txn_abc123');

    Event::assertDispatched(PaymentCaptured::class);
});

it('voids a payment', function () {
    Http::fake(['*/payments/void' => Http::response(paymentResponse(), 200)]);

    $response = DimePay::payments()->void('txn_abc123');

    expect($response)->toBeInstanceOf(PaymentResponseData::class);
});

it('fires PaymentVoided event', function () {
    Event::fake();
    Http::fake(['*/payments/void' => Http::response(paymentResponse(), 200)]);

    DimePay::payments()->void('txn_abc123');

    Event::assertDispatched(PaymentVoided::class);
});

it('refunds a payment', function () {
    Http::fake(['*/payments/refund' => Http::response(paymentResponse(), 200)]);

    $response = DimePay::payments()->refund('txn_abc123');

    expect($response)->toBeInstanceOf(PaymentResponseData::class);
});

it('fires PaymentRefunded event', function () {
    Event::fake();
    Http::fake(['*/payments/refund' => Http::response(paymentResponse(), 200)]);

    DimePay::payments()->refund('txn_abc123');

    Event::assertDispatched(PaymentRefunded::class);
});

it('throws DimePayAuthException on 401', function () {
    Http::fake([
        '*/payments/*' => Http::response(['statusCode' => 401, 'body' => ['message' => 'Unauthorized', 'response' => null]], 401),
    ]);

    expect(fn () => DimePay::payments()->hostedPage(makeOrderDataForPayment()))
        ->toThrow(DimePayAuthException::class);
});
