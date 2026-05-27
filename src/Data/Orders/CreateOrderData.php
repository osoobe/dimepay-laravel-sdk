<?php

declare(strict_types=1);

namespace Osoobe\DimePay\Data\Orders;

use Osoobe\DimePay\Data\Shared\PersonData;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;

final class CreateOrderData extends Data
{
    public function __construct(
        public ?string $id,
        public ?float $total,
        public ?float $subtotal,
        public ?string $currency,
        public ?string $email,
        public ?string $ipAddress,
        public ?string $referenceTransactionId,
        public ?string $webhookUrl = null,
        public ?string $redirectUrl = null,
        public ?string $checkoutUrl = null,
        public float $tax = 0,
        public float $discount = 0,
        public bool $fulfilled = false,
        public bool $tokenize = false,
        #[MapOutputName('is_subscription')]
        public bool $isSubscription = false,
        public string $orderComments = '',
        public array $items = [],
        public array $taxes = [],
        public ?PersonData $shippingPerson = null,
        public ?PersonData $billingPerson = null,
        public ?array $split = null,
        #[MapOutputName('subscription_instructions')]
        public ?SubscriptionInstructionsData $subscriptionInstructions = null,
    ) {}
}
