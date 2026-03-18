<?php

declare(strict_types=1);

namespace Osoobe\DimePay\Webhooks;

use Illuminate\Http\Request;
use Spatie\WebhookClient\WebhookProfile\WebhookProfile;

class DimePayWebhookProfile implements WebhookProfile
{
    /**
     * Determine if the webhook should be stored and processed.
     * Process all incoming DimePay webhook requests by default.
     * Consumers can override this binding to filter specific event types.
     */
    public function shouldProcess(Request $request): bool
    {
        return true;
    }
}
