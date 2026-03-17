<?php

declare(strict_types=1);

namespace Osoobe\DimePay\Data\Orders;

use Spatie\LaravelData\Data;

final class SplitData extends Data
{
    public function __construct(
        public string $merchantId,
        public int $amount,
        public int $fee,
    ) {}
}
