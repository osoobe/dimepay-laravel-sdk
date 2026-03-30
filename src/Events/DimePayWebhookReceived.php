<?php

declare(strict_types=1);

namespace Osoobe\DimePay\Events;

use Illuminate\Foundation\Events\Dispatchable;

class DimePayWebhookReceived
{
    use Dispatchable;

    public function __construct(
        public readonly string $type,
        public readonly array $payload,
    ) {}
}
