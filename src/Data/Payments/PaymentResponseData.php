<?php

declare(strict_types=1);

namespace Osoobe\DimePay\Data\Payments;

use Osoobe\DimePay\Data\Shared\CustomerData;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
final class PaymentResponseData extends Data
{
    public function __construct(
        public string $id,
        public float $amount,
        public float $finalAmount,
        public float $consumerFee,
        public string $currency,
        public string $status,
        public string $source,
        public ?string $description = null,
        public ?string $sourceAccount = null,
        public ?string $externalTransactionId = null,
        public ?string $entityId = null,
        public ?string $entityDisplayId = null,
        public ?string $entityType = null,
        public ?string $customerId = null,
        public ?CustomerData $customer = null,
        public ?string $brandId = null,
        public ?string $paymentMethodId = null,
        public bool $refunded = false,
        public bool $settled = false,
        public ?array $sourceDetails = null,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
    ) {}
}
