<?php

declare(strict_types=1);

namespace Osoobe\DimePay\Data\Orders;

use Spatie\LaravelData\Data;

final class SubscriptionInstructionsData extends Data
{
    public function __construct(
        public string $recurringFrequency,
        public int $billingCycles,
    ) {}
}
