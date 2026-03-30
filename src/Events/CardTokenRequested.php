<?php

declare(strict_types=1);

namespace Osoobe\DimePay\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Osoobe\DimePay\Data\Cards\CardRequestData;
use Osoobe\DimePay\Data\Cards\CardRequestResponseData;

class CardTokenRequested
{
    use Dispatchable;

    public function __construct(
        public readonly CardRequestData $request,
        public readonly CardRequestResponseData $response,
    ) {}
}
