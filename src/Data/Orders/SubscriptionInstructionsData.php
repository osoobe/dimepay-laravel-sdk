<?php

declare(strict_types=1);

namespace Osoobe\DimePay\Data\Orders;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;

final class SubscriptionInstructionsData extends Data
{
    public function __construct(
        #[MapOutputName('recurring_frequency')]
        public ?string $recurringFrequency,
        #[MapOutputName('billing_cycles')]
        public ?int $billingCycles,
    ) {}
}
