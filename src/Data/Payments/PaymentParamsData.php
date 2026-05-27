<?php

declare(strict_types=1);

namespace Osoobe\DimePay\Data\Payments;

use Spatie\LaravelData\Data;

final class PaymentParamsData extends Data
{
    public function __construct(
        public ?string $source,
        public ?string $token,
    ) {}
}
