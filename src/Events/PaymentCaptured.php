<?php

declare(strict_types=1);

namespace Osoobe\DimePay\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Osoobe\DimePay\Data\Payments\PaymentResponseData;

class PaymentCaptured
{
    use Dispatchable;

    public function __construct(
        public readonly string $transactionId,
        public readonly PaymentResponseData $response,
    ) {}
}
