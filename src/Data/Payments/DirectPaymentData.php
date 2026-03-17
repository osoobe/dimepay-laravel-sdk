<?php

declare(strict_types=1);

namespace Osoobe\DimePay\Data\Payments;

use Osoobe\DimePay\Data\Orders\OrderItemData;
use Osoobe\DimePay\Data\Shared\PersonData;
use Osoobe\DimePay\Data\Shared\TaxData;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class DirectPaymentData extends Data
{
    public function __construct(
        public string $id,
        public float $total,
        public float $subtotal,
        public string $currency,
        public string $email,
        public PaymentParamsData $paymentParams,
        public float $tax = 0,
        public float $discount = 0,
        public bool $fulfilled = false,
        public ?string $ipAddress = null,
        public ?string $orderComments = null,
        public ?string $referenceTransactionId = null,
        public ?string $webhookUrl = null,
        #[DataCollectionOf(OrderItemData::class)]
        public ?DataCollection $items = null,
        #[DataCollectionOf(TaxData::class)]
        public ?DataCollection $taxes = null,
        public ?PersonData $billingPerson = null,
        public ?PersonData $shippingPerson = null,
    ) {}
}
