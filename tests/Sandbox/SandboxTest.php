<?php

declare(strict_types=1);

/**
 * Comprehensive Sandbox Integration Tests
 *
 * Covers:
 * - Full order flow (create → fetch)
 * - Hosted payment page
 * - Split payments — Dime Bridge (single merchant, multi-merchant, multi-item)
 * - Recurring — WEEKLY, MONTHLY, YEARLY
 * - Card tokenization request
 * - Auth, Sale, Capture, Void, Refund (requires DIMEPAY_TEST_CARD_TOKEN)
 *
 * Usage:
 *   DIMEPAY_CLIENT_KEY=ck_xxx DIMEPAY_SECRET_KEY=sk_xxx vendor/bin/pest tests/Sandbox --group sandbox
 *
 * With card token:
 *   DIMEPAY_CLIENT_KEY=ck_xxx DIMEPAY_SECRET_KEY=sk_xxx DIMEPAY_TEST_CARD_TOKEN=card_xxx vendor/bin/pest tests/Sandbox --group sandbox
 */

use Osoobe\DimePay\Data\Cards\CardRequestData;
use Osoobe\DimePay\Data\Cards\CardRequestResponseData;
use Osoobe\DimePay\Data\Orders\CreateOrderData;
use Osoobe\DimePay\Data\Orders\CreateOrderResponseData;
use Osoobe\DimePay\Data\Orders\OrderResponseData;
use Osoobe\DimePay\Data\Orders\SubscriptionInstructionsData;
use Osoobe\DimePay\Data\Payments\DirectPaymentData;
use Osoobe\DimePay\Data\Payments\HostedPageResponseData;
use Osoobe\DimePay\Data\Payments\PaymentParamsData;
use Osoobe\DimePay\Data\Payments\PaymentResponseData;
use Osoobe\DimePay\Exceptions\DimePayException;
use Osoobe\DimePay\Http\DimePayClient;
use Osoobe\DimePay\Services\CardService;
use Osoobe\DimePay\Services\OrderService;
use Osoobe\DimePay\Services\PaymentService;
use Osoobe\DimePay\Tests\Sandbox\TestCards;
use Osoobe\DimePay\Tests\TestCase;

uses(TestCase::class)->group('sandbox');

// ─────────────────────────────────────────────────────────────
// Config & Helpers
// ─────────────────────────────────────────────────────────────

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

function sandboxApiClient(): DimePayClient { return new DimePayClient(sandboxApiConfig()); }

function sandboxOrderService(): OrderService { return new OrderService(sandboxApiClient()); }

function sandboxPaymentService(): PaymentService { return new PaymentService(sandboxApiClient()); }

function sandboxCardService(): CardService { return new CardService(sandboxApiClient()); }

function baseOrderFields(array $overrides = []): array
{
    return array_merge([
        'id'                     => 'TEST-' . uniqid(),
        'total'                  => 5000,
        'subtotal'               => 5000,
        'currency'               => 'JMD',
        'email'                  => 'test@example.com',
        'ipAddress'              => '127.0.0.1',
        'referenceTransactionId' => 'REF-' . uniqid(),
        'webhookUrl'             => 'https://example.com/webhook',
        'redirectUrl'            => 'https://example.com/callback',
        'checkoutUrl'            => 'https://example.com/checkout',
        'orderComments'          => 'Sandbox test',
        'items'                  => [
            [
                'id'               => 'item-1',
                'name'             => 'Test Item',
                'price'            => 5000,
                'quantity'         => 1,
                'sku'              => 'TEST-001',
                'shortDescription' => 'A test item',
                'imageUrl'         => 'https://example.com/image.jpg',
            ],
        ],
        'taxes' => [],
    ], $overrides);
}

function makeOrderData(array $overrides = []): CreateOrderData
{
    return CreateOrderData::from(baseOrderFields($overrides));
}

function makeDirectPaymentData(string $cardToken, array $overrides = []): DirectPaymentData
{
    return DirectPaymentData::from(array_merge(
        baseOrderFields($overrides),
        ['paymentParams' => ['source' => 'TOKEN', 'token' => $cardToken]]
    ));
}

function dumpException(DimePayException $e): void
{
    dump(['status' => $e->getStatus(), 'code' => $e->getErrorCode(), 'message' => $e->getMessage(), 'details' => $e->getDetails()]);
}

function getCardToken(): string
{
    $token = (string) getenv('DIMEPAY_TEST_CARD_TOKEN');
    if (empty($token)) {
        test()->skip('Set DIMEPAY_TEST_CARD_TOKEN to run card payment tests');
    }
    return $token;
}

// ─────────────────────────────────────────────────────────────
// Orders — Full Flow
// ─────────────────────────────────────────────────────────────

it('creates an order and returns a valid order_url', function () {
    try {
        $response = sandboxOrderService()->create(makeOrderData());
        expect($response)->toBeInstanceOf(CreateOrderResponseData::class);
        expect($response->orderUrl)->toContain('dimepay');
        dump(['✓ order_url' => $response->orderUrl]);
    } catch (DimePayException $e) { dumpException($e); throw $e; }
});

it('fetches an order by token after creation', function () {
    $created = sandboxOrderService()->create(makeOrderData());
    $token   = basename(parse_url($created->orderUrl, PHP_URL_PATH));
    expect($token)->not->toBeEmpty();

    try {
        $order = sandboxOrderService()->find($token);
        expect($order)->toBeInstanceOf(OrderResponseData::class);
        expect($order->status)->not->toBeEmpty();
        dump(['✓ id' => $order->id, '✓ status' => $order->status, '✓ currency' => $order->currency]);
    } catch (DimePayException $e) { dumpException($e); throw $e; }
});

it('creates an order with billing and shipping person', function () {
    try {
        $response = sandboxOrderService()->create(makeOrderData([
            'billingPerson' => [
                'name'                => 'Michael Scott',
                'street'              => '555 Lackawanna Ave',
                'city'                => 'Scranton',
                'stateOrProvinceName' => 'Pennsylvania',
                'stateOrProvinceCode' => 'PA',
                'postalCode'          => '18508',
                'countryName'         => 'United States',
                'countryCode'         => 'US',
                'email'               => 'mscott@example.com',
                'phone'               => '',
            ],
            'shippingPerson' => [
                'name'                => 'Michael Scott',
                'street'              => '555 Lackawanna Ave',
                'city'                => 'Scranton',
                'stateOrProvinceName' => 'Pennsylvania',
                'stateOrProvinceCode' => 'PA',
                'postalCode'          => '18508',
                'countryName'         => 'United States',
                'countryCode'         => 'US',
            ],
        ]));
        expect($response)->toBeInstanceOf(CreateOrderResponseData::class);
        dump(['✓ with persons' => $response->orderUrl]);
    } catch (DimePayException $e) { dumpException($e); throw $e; }
});

it('creates an order with multiple items', function () {
    try {
        $response = sandboxOrderService()->create(makeOrderData([
            'total'    => 10000,
            'subtotal' => 10000,
            'items'    => [
                ['id' => 'item-1', 'name' => 'Item One', 'price' => 4000, 'quantity' => 1, 'sku' => 'ITEM-1', 'shortDescription' => '', 'imageUrl' => 'https://example.com/img.jpg'],
                ['id' => 'item-2', 'name' => 'Item Two', 'price' => 3000, 'quantity' => 1, 'sku' => 'ITEM-2', 'shortDescription' => '', 'imageUrl' => 'https://example.com/img.jpg'],
                ['id' => 'item-3', 'name' => 'Item Three', 'price' => 1500, 'quantity' => 2, 'sku' => 'ITEM-3', 'shortDescription' => '', 'imageUrl' => 'https://example.com/img.jpg'],
            ],
        ]));
        expect($response)->toBeInstanceOf(CreateOrderResponseData::class);
        dump(['✓ multi-item order_url' => $response->orderUrl]);
    } catch (DimePayException $e) { dumpException($e); throw $e; }
});

// ─────────────────────────────────────────────────────────────
// Hosted Payment Page
// ─────────────────────────────────────────────────────────────

it('creates a hosted payment page', function () {
    try {
        $response = sandboxPaymentService()->hostedPage(makeOrderData());
        expect($response)->toBeInstanceOf(HostedPageResponseData::class);
        expect($response->orderUrl)->toContain('dimepay');
        dump(['✓ hosted page url' => $response->orderUrl]);
    } catch (DimePayException $e) { dumpException($e); throw $e; }
});

it('creates a hosted payment page with tokenize enabled', function () {
    try {
        $response = sandboxPaymentService()->hostedPage(makeOrderData(['tokenize' => true]));
        expect($response)->toBeInstanceOf(HostedPageResponseData::class);
        dump(['✓ tokenize hosted page url' => $response->orderUrl]);
    } catch (DimePayException $e) { dumpException($e); throw $e; }
});

// ─────────────────────────────────────────────────────────────
// Card Tokenization
// ─────────────────────────────────────────────────────────────

it('creates a card tokenization request', function () {
    try {
        $response = sandboxCardService()->requestToken(CardRequestData::from([
            'id'          => 'CARD-REQ-' . uniqid(),
            'webhookUrl'  => 'https://example.com/webhook',
            'redirectUrl' => 'https://example.com/card-saved',
        ]));
        expect($response)->toBeInstanceOf(CardRequestResponseData::class);
        expect($response->token)->not->toBeEmpty();
        expect($response->cardUrl)->not->toBeEmpty();
        dump(['✓ card_request_token' => $response->token, '✓ card_url' => $response->cardUrl]);
    } catch (DimePayException $e) { dumpException($e); throw $e; }
});

// ─────────────────────────────────────────────────────────────
// Dime Bridge — Split Payments
// ─────────────────────────────────────────────────────────────

it('creates a two-merchant split order', function () {
    try {
        $response = sandboxOrderService()->create(makeOrderData([
            'total'    => 2000,
            'subtotal' => 2000,
            'items'    => [
                ['id' => 'SKU-BOOK', 'name' => 'Book', 'price' => 500, 'quantity' => 1, 'sku' => 'BOOK', 'shortDescription' => '', 'imageUrl' => 'https://example.com/img.jpg', 'merchantId' => 'm4D8mQ1wMrdTUIg'],
                ['id' => 'SKU-COURSE', 'name' => 'Course', 'price' => 1500, 'quantity' => 1, 'sku' => 'COURSE', 'shortDescription' => '', 'imageUrl' => 'https://example.com/img.jpg', 'merchantId' => 'm7UarSiV9zWxN6v'],
            ],
            'split' => [
                ['merchantId' => 'm4D8mQ1wMrdTUIg', 'amount' => 500, 'fee' => 10],
                ['merchantId' => 'm7UarSiV9zWxN6v', 'amount' => 1500, 'fee' => 30],
            ],
        ]));
        expect($response)->toBeInstanceOf(CreateOrderResponseData::class);
        dump(['✓ two-merchant bridge' => $response->orderUrl]);
    } catch (DimePayException $e) { dumpException($e); throw $e; }
});

it('creates a single-merchant bridge order', function () {
    try {
        $response = sandboxOrderService()->create(makeOrderData([
            'items' => [
                ['id' => 'SKU-1', 'name' => 'Item', 'price' => 5000, 'quantity' => 1, 'sku' => 'SKU-1', 'shortDescription' => '', 'imageUrl' => 'https://example.com/img.jpg', 'merchantId' => 'm4D8mQ1wMrdTUIg'],
            ],
            'split' => [
                ['merchantId' => 'm4D8mQ1wMrdTUIg', 'amount' => 5000, 'fee' => 10],
            ],
        ]));
        expect($response)->toBeInstanceOf(CreateOrderResponseData::class);
        dump(['✓ single-merchant bridge' => $response->orderUrl]);
    } catch (DimePayException $e) { dumpException($e); throw $e; }
});

it('creates a bridge order with multiple items per merchant', function () {
    try {
        $response = sandboxOrderService()->create(makeOrderData([
            'total'    => 5000,
            'subtotal' => 5000,
            'items'    => [
                ['id' => 'item-1', 'name' => 'Book A', 'price' => 1000, 'quantity' => 1, 'sku' => 'BOOK-A', 'shortDescription' => '', 'imageUrl' => 'https://example.com/img.jpg', 'merchantId' => 'm4D8mQ1wMrdTUIg'],
                ['id' => 'item-2', 'name' => 'Book B', 'price' => 1000, 'quantity' => 1, 'sku' => 'BOOK-B', 'shortDescription' => '', 'imageUrl' => 'https://example.com/img.jpg', 'merchantId' => 'm4D8mQ1wMrdTUIg'],
                ['id' => 'item-3', 'name' => 'Course', 'price' => 3000, 'quantity' => 1, 'sku' => 'COURSE', 'shortDescription' => '', 'imageUrl' => 'https://example.com/img.jpg', 'merchantId' => 'm7UarSiV9zWxN6v'],
            ],
            'split' => [
                ['merchantId' => 'm4D8mQ1wMrdTUIg', 'amount' => 2000, 'fee' => 10],
                ['merchantId' => 'm7UarSiV9zWxN6v', 'amount' => 3000, 'fee' => 30],
            ],
        ]));
        expect($response)->toBeInstanceOf(CreateOrderResponseData::class);
        dump(['✓ multi-item bridge' => $response->orderUrl]);
    } catch (DimePayException $e) { dumpException($e); throw $e; }
});

it('creates a bridge order with zero platform fee', function () {
    try {
        $response = sandboxOrderService()->create(makeOrderData([
            'total'    => 2000,
            'subtotal' => 2000,
            'items'    => [
                ['id' => 'SKU-1', 'name' => 'Item A', 'price' => 1000, 'quantity' => 1, 'sku' => 'SKU-1', 'shortDescription' => '', 'imageUrl' => 'https://example.com/img.jpg', 'merchantId' => 'm4D8mQ1wMrdTUIg'],
                ['id' => 'SKU-2', 'name' => 'Item B', 'price' => 1000, 'quantity' => 1, 'sku' => 'SKU-2', 'shortDescription' => '', 'imageUrl' => 'https://example.com/img.jpg', 'merchantId' => 'm7UarSiV9zWxN6v'],
            ],
            'split' => [
                ['merchantId' => 'm4D8mQ1wMrdTUIg', 'amount' => 1000, 'fee' => 0],
                ['merchantId' => 'm7UarSiV9zWxN6v', 'amount' => 1000, 'fee' => 0],
            ],
        ]));
        expect($response)->toBeInstanceOf(CreateOrderResponseData::class);
        dump(['✓ zero-fee bridge' => $response->orderUrl]);
    } catch (DimePayException $e) { dumpException($e); throw $e; }
});

// ─────────────────────────────────────────────────────────────
// Recurring / Subscriptions
// ─────────────────────────────────────────────────────────────

it('creates a monthly subscription order', function () {
    try {
        $response = sandboxOrderService()->create(makeOrderData([
            'isSubscription'           => true,
            'tokenize'                 => true,
            'subscriptionInstructions' => ['recurringFrequency' => 'MONTHLY', 'billingCycles' => 12],
        ]));
        expect($response)->toBeInstanceOf(CreateOrderResponseData::class);
        dump(['✓ monthly sub' => $response->orderUrl]);
    } catch (DimePayException $e) { dumpException($e); throw $e; }
});

it('creates a weekly subscription order', function () {
    try {
        $response = sandboxOrderService()->create(makeOrderData([
            'isSubscription'           => true,
            'tokenize'                 => true,
            'subscriptionInstructions' => ['recurringFrequency' => 'WEEKLY', 'billingCycles' => 52],
        ]));
        expect($response)->toBeInstanceOf(CreateOrderResponseData::class);
        dump(['✓ weekly sub' => $response->orderUrl]);
    } catch (DimePayException $e) { dumpException($e); throw $e; }
});

it('creates a yearly subscription order', function () {
    try {
        $response = sandboxOrderService()->create(makeOrderData([
            'isSubscription'           => true,
            'tokenize'                 => true,
            'subscriptionInstructions' => ['recurringFrequency' => 'YEARLY', 'billingCycles' => 3],
        ]));
        expect($response)->toBeInstanceOf(CreateOrderResponseData::class);
        dump(['✓ yearly sub' => $response->orderUrl]);
    } catch (DimePayException $e) { dumpException($e); throw $e; }
});

it('creates a subscription using SubscriptionInstructionsData object directly', function () {
    try {
        $response = sandboxOrderService()->create(new CreateOrderData(
            id: 'SUB-OBJ-' . uniqid(),
            total: 9900,
            subtotal: 9900,
            currency: 'USD',
            email: 'premium@example.com',
            ipAddress: '127.0.0.1',
            referenceTransactionId: 'REF-SUB-' . uniqid(),
            isSubscription: true,
            tokenize: true,
            subscriptionInstructions: new SubscriptionInstructionsData(
                recurringFrequency: 'MONTHLY',
                billingCycles: 24,
            ),
            items: [
                ['id' => 'PLAN-PREMIUM', 'name' => 'Premium Plan', 'price' => 9900, 'quantity' => 1, 'sku' => 'PREMIUM', 'shortDescription' => '', 'imageUrl' => 'https://example.com/img.jpg'],
            ],
            taxes: [],
        ));
        expect($response)->toBeInstanceOf(CreateOrderResponseData::class);
        dump(['✓ SubscriptionInstructionsData object' => $response->orderUrl]);
    } catch (DimePayException $e) { dumpException($e); throw $e; }
});

it('creates a subscription without tokenize (for known card token users)', function () {
    try {
        $response = sandboxOrderService()->create(makeOrderData([
            'isSubscription'           => true,
            'tokenize'                 => false,
            'subscriptionInstructions' => ['recurringFrequency' => 'MONTHLY', 'billingCycles' => 6],
        ]));
        expect($response)->toBeInstanceOf(CreateOrderResponseData::class);
        dump(['✓ sub without tokenize' => $response->orderUrl]);
    } catch (DimePayException $e) { dumpException($e); throw $e; }
});

// ─────────────────────────────────────────────────────────────
// Card Payment Flows (require DIMEPAY_TEST_CARD_TOKEN)
// ─────────────────────────────────────────────────────────────

it('authorizes a payment', function () {
    $token = getCardToken();
    try {
        $response = sandboxPaymentService()->authorize(makeDirectPaymentData($token));
        expect($response)->toBeInstanceOf(PaymentResponseData::class);
        expect($response->id)->not->toBeEmpty();
        dump(['✓ auth' => $response->id, 'status' => $response->status]);
    } catch (DimePayException $e) { dumpException($e); throw $e; }
});

it('processes a sale', function () {
    $token = getCardToken();
    try {
        $response = sandboxPaymentService()->sale(makeDirectPaymentData($token));
        expect($response)->toBeInstanceOf(PaymentResponseData::class);
        dump(['✓ sale' => $response->id, 'status' => $response->status]);
    } catch (DimePayException $e) { dumpException($e); throw $e; }
});

it('authorizes then captures a payment', function () {
    $token = getCardToken();
    try {
        $auth = sandboxPaymentService()->authorize(makeDirectPaymentData($token));
        dump(['✓ auth' => $auth->id, 'status' => $auth->status]);
        $capture = sandboxPaymentService()->capture($auth->id);
        expect($capture)->toBeInstanceOf(PaymentResponseData::class);
        dump(['✓ capture' => $capture->id, 'status' => $capture->status]);
    } catch (DimePayException $e) { dumpException($e); throw $e; }
});

it('authorizes then voids a payment', function () {
    $token = getCardToken();
    try {
        $auth = sandboxPaymentService()->authorize(makeDirectPaymentData($token));
        dump(['✓ auth' => $auth->id]);
        $void = sandboxPaymentService()->void($auth->id);
        expect($void)->toBeInstanceOf(PaymentResponseData::class);
        dump(['✓ void' => $void->id, 'status' => $void->status]);
    } catch (DimePayException $e) { dumpException($e); throw $e; }
});

it('processes a sale then refunds it', function () {
    $token = getCardToken();
    try {
        $sale = sandboxPaymentService()->sale(makeDirectPaymentData($token));
        dump(['✓ sale' => $sale->id]);
        $refund = sandboxPaymentService()->refund($sale->id);
        expect($refund)->toBeInstanceOf(PaymentResponseData::class);
        dump(['✓ refund' => $refund->id, 'status' => $refund->status]);
    } catch (DimePayException $e) { dumpException($e); throw $e; }
});

// Per-brand hosted payment pages using TestCards
$brands = ['visa' => TestCards::VISA, 'mastercard' => TestCards::MASTERCARD, 'amex' => TestCards::AMEX];

foreach ($brands as $key => $card) {
    it("creates hosted page for {$card['brand']} card", function () use ($card) {
        try {
            $response = sandboxPaymentService()->hostedPage(makeOrderData());
            expect($response)->toBeInstanceOf(HostedPageResponseData::class);
            dump([
                "✓ {$card['brand']}" => $response->orderUrl,
                'test_card'          => $card['number'],
                'expiry'             => $card['expiration_date'],
                'cvv'                => $card['cvv'],
            ]);
        } catch (DimePayException $e) { dumpException($e); throw $e; }
    });
}
