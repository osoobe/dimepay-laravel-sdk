# DimePay Laravel SDK

[![Latest Version on Packagist](https://img.shields.io/packagist/v/osoobe/dimepay-laravel-sdk.svg?style=flat-square)](https://packagist.org/packages/osoobe/dimepay-laravel-sdk)
[![Tests](https://img.shields.io/github/actions/workflow/status/osoobe/dimepay-laravel-sdk/tests.yml?label=tests&style=flat-square)](https://github.com/osoobe/dimepay-laravel-sdk/actions)
[![PHP Version](https://img.shields.io/badge/php-%5E8.2-blue.svg?style=flat-square)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/laravel-10%20%7C%2011%20%7C%2012-red.svg?style=flat-square)](https://laravel.com)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg?style=flat-square)](LICENSE.md)

A first-class Laravel SDK for the [DimePay](https://docs.dimepay.net) payment gateway.

Supports orders, hosted payments, auth/capture/void/refund, card tokenization, split payments (Dime Bridge), recurring subscriptions, JWT signing, webhook handling, and full white-label extensibility.

---

## Requirements

| Dependency | Version |
|---|---|
| PHP | ^8.2 |
| Laravel | ^10.0 \| ^11.0 \| ^12.0 |

---

## Installation

```bash
composer require osoobe/dimepay-laravel-sdk
```

The package registers itself automatically via Laravel's package discovery. No manual provider registration needed.

### Run the install command

```bash
php artisan dimepay:install
```

This will:
- Publish `config/dimepay.php`
- Publish `config/webhook-client.php`
- Publish webhook migrations
- Print step-by-step setup instructions in your terminal

---

## Configuration

### 1. Environment Variables

Add these to your `.env` file:

```env
# Required
DIMEPAY_ENV=sandbox
DIMEPAY_CLIENT_KEY=ck_your_client_key
DIMEPAY_SECRET_KEY=sk_your_secret_key

# Optional — only needed if you receive webhooks
DIMEPAY_WEBHOOK_SECRET=your_webhook_secret
```

### 2. Config File

After publishing, open `config/dimepay.php`. Every option is documented inline. Here is the full reference:

```php
return [

    // 'sandbox' or 'production'
    // Controls which base URL is used for all API requests.
    'environment' => env('DIMEPAY_ENV', 'sandbox'),

    // Sent as the `client_key` header on every request.
    // Obtain from your DimePay dashboard → Developer section.
    'client_key' => env('DIMEPAY_CLIENT_KEY'),

    // Used server-side to sign JWT payloads. NEVER expose this client-side.
    'secret_key' => env('DIMEPAY_SECRET_KEY'),

    // Base URLs — only override if DimePay changes endpoints or you use a proxy.
    'base_urls' => [
        'production' => env('DIMEPAY_PRODUCTION_URL', 'https://api.dimepay.app/dapi/v1'),
        'sandbox'    => env('DIMEPAY_SANDBOX_URL', 'https://sandbox.api.dimepay.app/dapi/v1'),
    ],

    // HTTP client options
    'timeout'     => 30,   // seconds before a request is aborted
    'retries'     => 2,    // number of retries on server errors
    'retry_delay' => 500,  // milliseconds between retries

    // Logging — logs all API responses to your Laravel log channel
    'logging' => [
        'enabled' => env('DIMEPAY_LOGGING', true),
        'channel' => env('DIMEPAY_LOG_CHANNEL', 'stack'),
        'level'   => env('DIMEPAY_LOG_LEVEL', 'debug'),
    ],

    // Routes — controls the package's built-in webhook/callback routes
    'routes' => [
        'enabled'    => env('DIMEPAY_ROUTES_ENABLED', true),
        'prefix'     => env('DIMEPAY_ROUTES_PREFIX', 'dimepay'),
        'middleware' => ['api'],
    ],

    // Webhook signature verification
    'webhook' => [
        'secret'    => env('DIMEPAY_WEBHOOK_SECRET'),
        'tolerance' => 300, // replay-attack window in seconds
    ],

    // JWT signing options
    'jwt' => [
        'algorithm' => 'HS256',
        'ttl'       => 3600, // token time-to-live in seconds
    ],
];
```

### 3. Webhook Setup (if you receive payment events)

The package uses `spatie/laravel-webhook-client` for webhook handling. After publishing the configs:

**a) Configure `config/webhook-client.php`** — find or add the `dimepay` entry:

```php
'configs' => [
    [
        'name'                  => 'dimepay',
        'signing_secret'        => env('DIMEPAY_WEBHOOK_SECRET'),
        'signature_header_name' => 'X-DimePay-Signature',
        'signature_validator'   => \Osoobe\DimePay\Webhooks\DimePaySignatureValidator::class,
        'webhook_profile'       => \Osoobe\DimePay\Webhooks\DimePayWebhookProfile::class,
        'webhook_response'      => \Spatie\WebhookClient\WebhookResponse\DefaultRespondsTo::class,
        'webhook_model'         => \Spatie\WebhookClient\Models\WebhookCall::class,
        'process_webhook_job'   => \Osoobe\DimePay\Webhooks\ProcessDimePayWebhookJob::class,
    ],
],
```

**b) Run migrations:**

```bash
php artisan migrate
```

**c) Exclude the webhook route from CSRF** in `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: [
        'dimepay/webhook',
    ]);
})
```

**d) Set your webhook URL in DimePay dashboard** to:

```
https://yourapp.com/dimepay/webhook
```

---

## How It Works

Every API request to DimePay follows this flow:

1. You call a service method e.g. `DimePay::orders()->create($data)`
2. The SDK signs your payload as a JWT using your `secret_key`
3. The signed JWT is wrapped as `{ lang: "en", data: "<jwt>" }`
4. The request is sent with the `client_key` header
5. The response is hydrated into a typed DTO
6. A Laravel event is fired

---

## Usage

### The Facade

All services are accessible via the `DimePay` facade:

```php
use Osoobe\DimePay\Facades\DimePay;

DimePay::orders()    // OrderServiceInterface
DimePay::payments()  // PaymentServiceInterface
DimePay::cards()     // CardServiceInterface
DimePay::jwt()       // JwtSigner
```

You can also inject services directly via their interfaces:

```php
use Osoobe\DimePay\Contracts\OrderServiceInterface;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly OrderServiceInterface $orders,
    ) {}
}
```

---

### Orders

#### Create an Order

```php
use Osoobe\DimePay\Facades\DimePay;
use Osoobe\DimePay\Data\Orders\CreateOrderData;
use Osoobe\DimePay\Data\Orders\OrderItemData;
use Osoobe\DimePay\Data\Shared\PersonData;

$order = DimePay::orders()->create(new CreateOrderData(
    id: 'ORDER-001',
    total: 5000,
    subtotal: 5000,
    currency: 'JMD',
    email: 'customer@example.com',
    webhookUrl: 'https://yourapp.com/dimepay/webhook',
    redirectUrl: 'https://yourapp.com/dimepay/callback',
    checkoutUrl: 'https://yourapp.com/checkout',
    items: OrderItemData::collect([
        [
            'id'       => 'item-1',
            'name'     => 'iMac',
            'price'    => 5000,
            'quantity' => 1,
            'sku'      => 'IMAC-001',
        ],
    ]),
    billingPerson: new PersonData(
        name: 'John Doe',
        street: '1 Test Ave',
        city: 'Kingston',
        stateOrProvinceName: 'Kingston',
        postalCode: '00000',
        countryName: 'Jamaica',
        email: 'john@example.com',
    ),
));

// $order is an OrderResponseData instance
echo $order->token;  // use this for payments
echo $order->status; // 'PENDING'
```

#### Retrieve an Order

```php
$order = DimePay::orders()->find('order_abc123');
```

---

### Payments

#### Hosted Payment Page

Redirects the customer to a DimePay-hosted checkout. Simplest integration.

```php
use Osoobe\DimePay\Data\Orders\CreateOrderData;

$page = DimePay::payments()->hostedPage(new CreateOrderData(
    id: 'ORDER-001',
    total: 5000,
    subtotal: 5000,
    currency: 'JMD',
    email: 'customer@example.com',
    redirectUrl: 'https://yourapp.com/dimepay/callback',
    webhookUrl: 'https://yourapp.com/dimepay/webhook',
));

return redirect($page->orderUrl);
```

#### Authorize (Hold Funds)

Authorize funds now, capture later. Useful when you want to confirm stock before charging.

```php
use Osoobe\DimePay\Data\Payments\DirectPaymentData;
use Osoobe\DimePay\Data\Payments\PaymentParamsData;

$payment = DimePay::payments()->authorize(new DirectPaymentData(
    id: 'ORDER-001',
    total: 5000,
    subtotal: 5000,
    currency: 'JMD',
    email: 'customer@example.com',
    paymentParams: new PaymentParamsData(
        source: 'TOKEN',
        token: 'card_abc123', // saved card token
    ),
));

$transactionId = $payment->id;
```

#### Sale (Authorize + Capture Immediately)

```php
$payment = DimePay::payments()->sale(new DirectPaymentData(
    id: 'ORDER-001',
    total: 5000,
    subtotal: 5000,
    currency: 'JMD',
    email: 'customer@example.com',
    paymentParams: new PaymentParamsData(source: 'TOKEN', token: 'card_abc123'),
));
```

#### Capture an Authorized Payment

```php
$payment = DimePay::payments()->capture('transaction-id-here');
```

#### Void a Payment

```php
$payment = DimePay::payments()->void('transaction-id-here');
```

#### Refund a Payment

```php
$payment = DimePay::payments()->refund('transaction-id-here');
```

---

### Cards (Tokenization)

Tokenize a card without processing a payment. The customer completes the card form on DimePay's hosted page, and you receive the card token via webhook.

```php
use Osoobe\DimePay\Data\Cards\CardRequestData;

// Step 1 — request a card token
$cardRequest = DimePay::cards()->requestToken(new CardRequestData(
    id: 'CARD-REQ-001',
    webhookUrl: 'https://yourapp.com/dimepay/webhook',
    redirectUrl: 'https://yourapp.com/card-saved',
));

// Step 2 — redirect customer to the card form
return redirect($cardRequest->cardUrl);

// Step 3 — DimePay calls your webhook with the saved card token
// Listen to DimePayWebhookReceived event in your app
```

#### Retrieve a Saved Card

```php
$card = DimePay::cards()->find('cr_abc123');

echo $card->token;          // use for future payments
echo $card->lastFourDigits; // '1111'
echo $card->cardScheme;     // 'Visa'
```

---

### Dime Bridge (Split Payments)

Split a single payment across multiple merchants. Each item must be tagged with a `merchant_id`.

```php
use Osoobe\DimePay\Data\Orders\CreateOrderData;
use Osoobe\DimePay\Data\Orders\OrderItemData;
use Osoobe\DimePay\Data\Orders\SplitData;

$order = DimePay::orders()->create(new CreateOrderData(
    id: 'ORDER-001',
    total: 2000,
    subtotal: 2000,
    currency: 'USD',
    email: 'customer@example.com',
    items: OrderItemData::collect([
        ['id' => 'item-1', 'name' => 'Book', 'price' => 500, 'quantity' => 1, 'sku' => 'BOOK-1', 'merchantId' => 'm4D8mQ1wMrdTUIg'],
        ['id' => 'item-2', 'name' => 'Course', 'price' => 1500, 'quantity' => 1, 'sku' => 'COURSE-1', 'merchantId' => 'm7UarSiV9zWxN6v'],
    ]),
    split: SplitData::collect([
        ['merchantId' => 'm4D8mQ1wMrdTUIg', 'amount' => 500, 'fee' => 10],
        ['merchantId' => 'm7UarSiV9zWxN6v', 'amount' => 1500, 'fee' => 30],
    ]),
));
```

> `fee` is a percentage of the merchant's split amount (e.g. `10` = 10%).

---

### Recurring / Subscriptions

Add `isSubscription: true` and `subscriptionInstructions` to any order. Include `tokenize: true` to store the card for future billing cycles.

```php
use Osoobe\DimePay\Data\Orders\SubscriptionInstructionsData;

$order = DimePay::orders()->create(new CreateOrderData(
    id: 'SUB-001',
    total: 4900,
    subtotal: 4900,
    currency: 'USD',
    email: 'subscriber@example.com',
    isSubscription: true,
    tokenize: true,
    subscriptionInstructions: new SubscriptionInstructionsData(
        recurringFrequency: 'MONTHLY', // 'WEEKLY', 'MONTHLY', 'YEARLY'
        billingCycles: 12,
    ),
));

return redirect(DimePay::payments()->hostedPage($order)->orderUrl);
```

---

### JWT Signing (Manual)

The SDK signs all payloads automatically. If you need to sign manually for a frontend SDK:

```php
$jwt = DimePay::jwt()->sign([
    'id'         => 'ORDER-001',
    'total'      => 5000,
    'currency'   => 'JMD',
    'webhookUrl' => 'https://yourapp.com/dimepay/webhook',
]);
```

Pass this JWT as the `data` prop to the DimePay Flutter or Web SDK.

---

### Webhooks

DimePay sends payment events to `POST /dimepay/webhook`. The package handles receiving, storing, and queuing webhook processing automatically.

#### Listen to Webhook Events

In your `EventServiceProvider` or `AppServiceProvider`:

```php
use Osoobe\DimePay\Events\DimePayWebhookReceived;
use App\Listeners\HandleDimePayWebhook;

protected $listen = [
    DimePayWebhookReceived::class => [
        HandleDimePayWebhook::class,
    ],
];
```

#### Handle the Event

```php
namespace App\Listeners;

use Osoobe\DimePay\Events\DimePayWebhookReceived;

class HandleDimePayWebhook
{
    public function handle(DimePayWebhookReceived $event): void
    {
        $type    = $event->type;    // e.g. 'payment.success'
        $payload = $event->payload; // full webhook payload array

        match ($type) {
            'payment.success' => $this->handleSuccess($payload),
            'payment.failed'  => $this->handleFailed($payload),
            'card.saved'      => $this->handleCardSaved($payload),
            default           => null,
        };
    }
}
```

---

### Events Reference

All events fire from service methods — not controllers. They fire whether you use the built-in routes or call services directly.

| Event | Fired When | Properties |
|---|---|---|
| `OrderCreated` | Order created | `$request`, `$response` |
| `HostedPaymentPageCreated` | Hosted page created | `$request`, `$response` |
| `PaymentAuthorized` | Payment authorized | `$request`, `$response` |
| `PaymentSaleProcessed` | Sale completed | `$request`, `$response` |
| `PaymentCaptured` | Payment captured | `$transactionId`, `$response` |
| `PaymentVoided` | Payment voided | `$transactionId`, `$response` |
| `PaymentRefunded` | Payment refunded | `$transactionId`, `$response` |
| `CardTokenRequested` | Card tokenization initiated | `$request`, `$response` |
| `DimePayWebhookReceived` | Webhook received | `$type`, `$payload` |
| `DimePayRequestFailed` | Any API request fails | `$endpoint`, `$payload`, `$exception` |

---

### Exception Handling

All DimePay API errors are normalized into typed exceptions.

```php
use Osoobe\DimePay\Exceptions\DimePayAuthException;
use Osoobe\DimePay\Exceptions\DimePayValidationException;
use Osoobe\DimePay\Exceptions\DimePayNotFoundException;
use Osoobe\DimePay\Exceptions\DimePayServerException;
use Osoobe\DimePay\Exceptions\DimePayException;

try {
    $order = DimePay::orders()->create($data);
} catch (DimePayAuthException $e) {
    // HTTP 401 — invalid client_key
    Log::error('DimePay auth failed', ['code' => $e->getErrorCode()]);
} catch (DimePayValidationException $e) {
    // HTTP 400 — bad payload
    return back()->withErrors($e->getDetails());
} catch (DimePayNotFoundException $e) {
    // HTTP 404 — order/payment not found
    abort(404);
} catch (DimePayServerException $e) {
    // HTTP 500 — DimePay server error
    abort(503, 'Payment service unavailable');
} catch (DimePayException $e) {
    // catch-all
}
```

Every exception exposes:

```php
$e->getMessage();    // human-readable message from DimePay
$e->getErrorCode();  // DimePay error code string e.g. 'invalid_token'
$e->getDetails();    // array — additional context from the error body
$e->getStatus();     // HTTP status code integer
```

---

## Sandbox Testing

Set `DIMEPAY_ENV=sandbox` in your `.env`. Use these test card credentials:

| Field | Value |
|---|---|
| Card Number | `4111 1111 1111 1111` |
| Expiry | `12/25` |
| CVV | `123` |

---

## White-label & Extension Guide

The package is built for override-everything extensibility. Nothing is hardcoded that cannot be swapped.

### Override a Service

Bind your own implementation in a service provider:

```php
use Osoobe\DimePay\Contracts\OrderServiceInterface;
use App\Services\MyOrderService;

$this->app->bind(OrderServiceInterface::class, MyOrderService::class);
```

Your class can extend the package's `OrderService` and override only what you need:

```php
use Osoobe\DimePay\Services\OrderService;

class MyOrderService extends OrderService
{
    public function create(CreateOrderData $data): OrderResponseData
    {
        // your custom logic before/after
        return parent::create($data);
    }
}
```

### Multi-tenant (Different Credentials Per Tenant)

```php
$payment = DimePay::withConfig([
    'client_key'  => $tenant->dimepay_client_key,
    'secret_key'  => $tenant->dimepay_secret_key,
    'environment' => $tenant->dimepay_env,
])->payments()->sale($data);
```

### Override the Callback Controller

Publish the routes:

```bash
php artisan vendor:publish --tag=dimepay-routes
```

Point to your own controller in `routes/dimepay.php`:

```php
Route::get('/dimepay/callback', [MyCallbackController::class, 'handle']);
```

### Override the Webhook Profile

Filter which webhook types get stored and processed:

```php
use Spatie\WebhookClient\WebhookProfile\WebhookProfile;

class MyWebhookProfile implements WebhookProfile
{
    public function shouldProcess(Request $request): bool
    {
        return in_array($request->input('type'), [
            'payment.success',
            'payment.failed',
            'card.saved',
        ]);
    }
}
```

Register in `config/webhook-client.php`:

```php
'webhook_profile' => \App\Webhooks\MyWebhookProfile::class,
```

---

## Testing Your App

Use `Http::fake()` to test without hitting the real DimePay API:

```php
use Illuminate\Support\Facades\Http;
use Osoobe\DimePay\Facades\DimePay;

Http::fake([
    'sandbox.api.dimepay.app/*/orders' => Http::response([
        'id'       => 'ORDER-001',
        'token'    => 'fake-token-123',
        'status'   => 'PENDING',
        'currency' => 'JMD',
        'total'    => 5000,
        'subtotal' => 5000,
    ], 201),
]);

$order = DimePay::orders()->create($orderData);

expect($order->token)->toBe('fake-token-123');
```

---

## `php artisan about`

The SDK registers itself with Laravel's `about` command:

```bash
php artisan about
```

```
DimePay SDK
  Environment ........ sandbox
  Logging ............ enabled
  Version ............ 1.0.0
```

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

---

## License

MIT — see [LICENSE.md](LICENSE.md).
