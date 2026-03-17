<?php

declare(strict_types=1);

namespace Osoobe\DimePay\Data\Cards;

use Spatie\LaravelData\Data;

final class CardRequestResponseData extends Data
{
    public function __construct(
        public string $token,
        public string $cardUrl,
        public string $referenceId,
        public string $status,
        public bool $expired,
        public ?string $currency = null,
        public ?string $cardExpiry = null,
        public ?string $cardScheme = null,
        public ?string $lastFourDigits = null,
        public int $verificationAttempts = 0,
    ) {}
}
