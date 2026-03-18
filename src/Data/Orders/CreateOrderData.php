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
        public string $ipAddress,
        public string $referenceTransactionId,
        public ?string $webhookUrl = null,
        public ?string $redirectUrl = null,
        public ?string $checkoutUrl = null,
        public float $tax = 0,
        public float $discount = 0,
        public bool $fulfilled = false,
        public bool $tokenize = false,
        public bool $isSubscription = false,
        public string $orderComments = '',
        /** @var OrderItemData[]|DataCollection|null */
        #[DataCollectionOf(OrderItemData::class)]
        public DataCollection|array|null $items = null,
        /** @var TaxData[]|DataCollection|null */
        #[DataCollectionOf(TaxData::class)]
        public DataCollection|array|null $taxes = null,
        public ?PersonData $shippingPerson = null,
        public ?PersonData $billingPerson = null,
        /** @var SplitData[]|DataCollection|null */
        #[DataCollectionOf(SplitData::class)]
        public DataCollection|array|null $split = null,
        public ?SubscriptionInstructionsData $subscriptionInstructions = null,
    ) {}
}
