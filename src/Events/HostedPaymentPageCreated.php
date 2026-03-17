<?php

declare(strict_types=1);

namespace Osoobe\DimePay\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Osoobe\DimePay\Data\Orders\CreateOrderData;
use Osoobe\DimePay\Data\Payments\HostedPageResponseData;

class HostedPaymentPageCreated
{
    use Dispatchable;

    public function __construct(
        public readonly CreateOrderData $request,
        public readonly HostedPageResponseData $response,
    ) {}
}
