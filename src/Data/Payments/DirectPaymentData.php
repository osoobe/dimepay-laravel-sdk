<?php

declare(strict_types=1);

namespace Osoobe\DimePay\Data\Payments;

use Osoobe\DimePay\Data\Shared\PersonData;
use Spatie\LaravelData\Data;

final class DirectPaymentData extends Data
{
    public function __construct(
        public string $id,
        public float $total,
        public float $subtotal,
        public string $currency,
        public string $email,
        public string $ipAddress,
        public string $referenceTransactionId,
        public PaymentParamsData $paymentParams,
        public float $tax = 0,
        public float $discount = 0,
        public bool $fulfilled = false,
        public string $orderComments = '',
        public ?string $webhookUrl = null,
        public array $items = [],
        public array $taxes = [],
        public ?PersonData $billingPerson = null,
        public ?PersonData $shippingPerson = null,
    ) {}
}
