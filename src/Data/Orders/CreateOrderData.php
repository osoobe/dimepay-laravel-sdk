<?php

declare(strict_types=1);

namespace Osoobe\DimePay\Data\Orders;

use Osoobe\DimePay\Data\Shared\PersonData;
use Osoobe\DimePay\Data\Shared\TaxData;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class CreateOrderData extends Data
{
    public function __construct(
        public string $id,
        public float $total,
        public float $subtotal,
        public string $currency,
        public string $email,
        public ?string $webhookUrl = null,
        public ?string $redirectUrl = null,
        public ?string $checkoutUrl = null,
        public float $tax = 0,
        public float $discount = 0,
        public bool $fulfilled = false,
        public bool $tokenize = false,
        public bool $isSubscription = false,
        public ?string $ipAddress = null,
        public ?string $orderComments = null,
        public ?string $referenceTransactionId = null,
        public array $fees = [],
        #[DataCollectionOf(OrderItemData::class)]
        public ?DataCollection $items = null,
        #[DataCollectionOf(TaxData::class)]
        public ?DataCollection $taxes = null,
        public ?PersonData $shippingPerson = null,
        public ?PersonData $billingPerson = null,
        #[DataCollectionOf(SplitData::class)]
        public ?DataCollection $split = null,
        public ?SubscriptionInstructionsData $subscriptionInstructions = null,
    ) {}
}
