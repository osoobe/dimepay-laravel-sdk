<?php

declare(strict_types=1);

namespace Osoobe\DimePay\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Osoobe\DimePay\Data\Payments\DirectPaymentData;
use Osoobe\DimePay\Data\Payments\PaymentResponseData;

class PaymentAuthorized
{
    use Dispatchable;

    public function __construct(
        public readonly DirectPaymentData $request,
        public readonly PaymentResponseData $response,
    ) {}
}
