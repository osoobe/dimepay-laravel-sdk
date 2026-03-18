<?php

declare(strict_types=1);

namespace Osoobe\DimePay\Webhooks;

use Osoobe\DimePay\Events\DimePayWebhookReceived;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob;

class ProcessDimePayWebhookJob extends ProcessWebhookJob
{
    public function handle(): void
    {
        $payload = $this->webhookCall->payload;
        $type    = $payload['type'] ?? 'unknown';

        event(new DimePayWebhookReceived($type, $payload));
    }
}
