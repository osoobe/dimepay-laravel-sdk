<?php

declare(strict_types=1);

namespace Osoobe\DimePay\Data\Payments;

use Spatie\LaravelData\Data;

final class HostedPageResponseData extends Data
{
    public function __construct(
        public string $orderUrl,
    ) {}
}
