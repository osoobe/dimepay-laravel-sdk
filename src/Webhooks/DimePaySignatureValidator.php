<?php

declare(strict_types=1);

namespace Osoobe\DimePay\Webhooks;

use Illuminate\Http\Request;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator;
use Spatie\WebhookClient\WebhookConfig;

class DimePaySignatureValidator implements SignatureValidator
{
    /**
     * Validate the incoming webhook signature.
     *
     * DimePay has not yet published their webhook signing spec.
     * When they do, implement HMAC verification here using the
     * signature header and `dimepay.webhook.secret` config value.
     *
     * Until then, all incoming requests pass validation.
     */
    public function isValid(Request $request, WebhookConfig $config): bool
    {
        $secret = $config->signingSecret;

        if (empty($secret)) {
            return true;
        }

        // TODO: replace with DimePay's actual signature verification
        // once they publish their webhook signing spec.
        // Example HMAC pattern:
        // $signature = $request->header($config->signatureHeaderName);
        // $computed  = hash_hmac('sha256', $request->getContent(), $secret);
        // return hash_equals($computed, $signature);

        return true;
    }
}
