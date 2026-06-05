# Changelog

All notable changes to `osoobe/dimepay-laravel-sdk` will be documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Laravel 13 support — tested against PHP 8.3 and 8.4
- `LaravelCompatibilityTest` suite — verifies facade resolution, config binding, order creation, event dispatch, and HTTP faking across all supported Laravel versions (10–13)
- PHP 8.4 added to CI matrix

### Changed
- CI matrix expanded: PHP 8.3 × Laravel 13, PHP 8.4 × Laravel 13
- `orchestra/testbench` dev requirement widened to `^9.0|^10.0|^11.0`
- `pestphp/pest` and `pestphp/pest-plugin-laravel` dev requirements widened to `^3.0|^4.0` (Pest 4 is required for Laravel 13 jobs)
- CI `fail-fast` set to `false` — all matrix jobs now run to completion independently

### Fixed
- CardData now accepts partial responses from GET /cards/{cardRequestToken} — five identity/state fields made nullable.

## [1.0.0] - 2025-03-17

### Added
- Initial release
- `OrderService` — create and retrieve orders
- `PaymentService` — hosted page, auth, sale, capture, void, refund
- `CardService` — card tokenization request and retrieval
- `JwtSigner` — server-side JWT signing for all API payloads
- `DimePayClient` — HTTP client with `client_key` auth, retries, logging
- Full exception hierarchy — `DimePayAuthException`, `DimePayValidationException`, `DimePayNotFoundException`, `DimePayServerException`
- Dime Bridge support — split payments across multiple merchants
- Recurring/subscription support via `is_subscription` and `subscription_instructions`
- Webhook handling via `spatie/laravel-webhook-client` — queued processing, DB storage, signature verification stub
- Laravel events — `OrderCreated`, `PaymentAuthorized`, `PaymentSaleProcessed`, `PaymentCaptured`, `PaymentVoided`, `PaymentRefunded`, `HostedPaymentPageCreated`, `CardTokenRequested`, `DimePayWebhookReceived`, `DimePayRequestFailed`
- `DimePayManager` with `withConfig()` for multi-tenant support
- `DimePay` facade
- `dimepay:install` Artisan command
- Full test suite with Pest — 58 passing tests
- GitHub Actions CI across PHP 8.2/8.3 and Laravel 10/11/12
