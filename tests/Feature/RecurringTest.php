<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Osoobe\DimePay\Data\Orders\CreateOrderData;
use Osoobe\DimePay\Data\Orders\CreateOrderResponseData;
use Osoobe\DimePay\Data\Orders\SubscriptionInstructionsData;
use Osoobe\DimePay\Events\OrderCreated;
use Osoobe\DimePay\Exceptions\DimePayValidationException;
use Osoobe\DimePay\Facades\DimePay;

function makeRecurringOrderData(array $overrides = []): CreateOrderData
{
    return CreateOrderData::from(array_merge([
        'id'                      => 'SUB-001',
        'total'                   => 4900,
        'subtotal'                => 4900,
        'currency'                => 'USD',
        'email'                   => 'subscriber@example.com',
        'ipAddress'               => '127.0.0.1',
        'referenceTransactionId'  => 'REF-SUB-001',
        'isSubscription'          => true,
        'tokenize'                => true,
        'subscriptionInstructions' => [
            'recurringFrequency' => 'MONTHLY',
            'billingCycles'      => 12,
        ],
        'items' => [
            [
                'id'               => 'PLAN-BASIC',
                'name'             => 'Basic Plan – Monthly',
                'price'            => 4900,
                'quantity'         => 1,
                'sku'              => 'PLAN-BASIC',
                'shortDescription' => 'Monthly subscription',
                'imageUrl'         => 'https://example.com/plan.jpg',
            ],
        ],
        'taxes' => [],
    ], $overrides));
}

it('creates a monthly recurring subscription order', function () {
    Http::fake([
        '*/orders' => Http::response(['order_url' => 'https://sandbox.dimepay.app/e-order/sub123'], 201),
    ]);

    $response = DimePay::orders()->create(makeRecurringOrderData());

    expect($response)->toBeInstanceOf(CreateOrderResponseData::class);
    expect($response->orderUrl)->not->toBeEmpty();
});

it('creates a weekly recurring subscription order', function () {
    Http::fake([
        '*/orders' => Http::response(['order_url' => 'https://sandbox.dimepay.app/e-order/sub456'], 201),
    ]);

    $response = DimePay::orders()->create(makeRecurringOrderData([
        'subscriptionInstructions' => [
            'recurringFrequency' => 'WEEKLY',
            'billingCycles'      => 52,
        ],
    ]));

    expect($response)->toBeInstanceOf(CreateOrderResponseData::class);
    expect($response->orderUrl)->not->toBeEmpty();
});

it('creates a yearly recurring subscription order', function () {
    Http::fake([
        '*/orders' => Http::response(['order_url' => 'https://sandbox.dimepay.app/e-order/sub789'], 201),
    ]);

    $response = DimePay::orders()->create(makeRecurringOrderData([
        'subscriptionInstructions' => [
            'recurringFrequency' => 'YEARLY',
            'billingCycles'      => 3,
        ],
    ]));

    expect($response)->toBeInstanceOf(CreateOrderResponseData::class);
    expect($response->orderUrl)->not->toBeEmpty();
});

it('fires OrderCreated event for a recurring subscription', function () {
    Event::fake();

    Http::fake([
        '*/orders' => Http::response(['order_url' => 'https://sandbox.dimepay.app/e-order/sub123'], 201),
    ]);

    DimePay::orders()->create(makeRecurringOrderData());

    Event::assertDispatched(OrderCreated::class, function ($event) {
        return $event->request->isSubscription === true;
    });
});

it('creates a subscription with SubscriptionInstructionsData object', function () {
    Http::fake([
        '*/orders' => Http::response(['order_url' => 'https://sandbox.dimepay.app/e-order/sub999'], 201),
    ]);

    $response = DimePay::orders()->create(new CreateOrderData(
        id: 'SUB-002',
        total: 9900,
        subtotal: 9900,
        currency: 'USD',
        email: 'premium@example.com',
        ipAddress: '127.0.0.1',
        referenceTransactionId: 'REF-SUB-002',
        isSubscription: true,
        tokenize: true,
        subscriptionInstructions: new SubscriptionInstructionsData(
            recurringFrequency: 'MONTHLY',
            billingCycles: 24,
        ),
        items: [
            [
                'id'               => 'PLAN-PREMIUM',
                'name'             => 'Premium Plan',
                'price'            => 9900,
                'quantity'         => 1,
                'sku'              => 'PLAN-PREMIUM',
                'shortDescription' => 'Premium monthly plan',
                'imageUrl'         => 'https://example.com/plan.jpg',
            ],
        ],
        taxes: [],
    ));

    expect($response)->toBeInstanceOf(CreateOrderResponseData::class);
    expect($response->orderUrl)->not->toBeEmpty();
});

it('creates a subscription without tokenize — for existing card token users', function () {
    Http::fake([
        '*/orders' => Http::response(['order_url' => 'https://sandbox.dimepay.app/e-order/sub000'], 201),
    ]);

    $response = DimePay::orders()->create(makeRecurringOrderData([
        'tokenize' => false,
    ]));

    expect($response)->toBeInstanceOf(CreateOrderResponseData::class);
    expect($response->orderUrl)->not->toBeEmpty();
});

it('throws DimePayValidationException for invalid recurring frequency', function () {
    Http::fake([
        '*/orders' => Http::response([
            'statusCode' => 400,
            'body'       => [
                'message'  => ['Invalid recurring frequency'],
                'response' => null,
            ],
        ], 400),
    ]);

    expect(fn () => DimePay::orders()->create(makeRecurringOrderData([
        'subscriptionInstructions' => [
            'recurringFrequency' => 'INVALID_FREQUENCY',
            'billingCycles'      => 12,
        ],
    ])))->toThrow(DimePayValidationException::class);
});
