<?php

declare(strict_types=1);

namespace Osoobe\DimePay\Data\Orders;

use Osoobe\DimePay\Data\Shared\CustomerData;
use Spatie\LaravelData\Data;

final class OrderResponseData extends Data
{
    public function __construct(
        public string $id,
        public string $token,
        public string $status,
        public string $currency,
        public float $total,
        public float $subtotal,
        public ?CustomerData $customer = null,
        public ?string $customerId = null,
        public ?string $originType = null,
        public ?string $originId = null,
        public array $products = [],
        public ?array $paymentSource = null,
        public ?array $fees = null,
        public float $shipping = 0,
        public float $tax = 0,
        public float $taxValue = 0,
        public float $consumerFee = 0,
        public bool $fulfilled = false,
        public bool $enableNotes = false,
        public ?string $notes = null,
        public ?array $metadata = null,
    ) {}
}
