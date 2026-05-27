<?php

declare(strict_types=1);

namespace Osoobe\DimePay\Data\Orders;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;

final class SplitData extends Data
{
    public function __construct(
        #[MapOutputName('merchant_id')]
        public ?string $merchantId,
        public ?int $amount,
        public ?int $fee,
    ) {}
}
