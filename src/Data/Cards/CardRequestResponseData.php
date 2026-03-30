<?php

declare(strict_types=1);

namespace Osoobe\DimePay\Data\Cards;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
final class CardRequestResponseData extends Data
{
    public function __construct(
        public string $token,
        public string $cardUrl,
        public ?string $referenceId = null,
        public ?string $status = null,
        public bool $expired = false,
        public ?string $currency = null,
        public ?string $cardExpiry = null,
        public ?string $cardScheme = null,
        public ?string $lastFourDigits = null,
        public int $verificationAttempts = 0,
    ) {}
}
