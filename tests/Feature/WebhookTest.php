<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Osoobe\DimePay\Events\DimePayWebhookReceived;
use Osoobe\DimePay\Webhooks\ProcessDimePayWebhookJob;
use Spatie\WebhookClient\Models\WebhookCall;

it('ProcessDimePayWebhookJob fires DimePayWebhookReceived event', function () {
    Event::fake();

    $payload = ['type' => 'payment.success', 'amount' => 5000];
    $webhookCall = Mockery::mock(WebhookCall::class);
    $webhookCall->shouldReceive('getAttribute')->with('payload')->andReturn($payload);

    $job = new ProcessDimePayWebhookJob($webhookCall);
    $job->handle();

    Event::assertDispatched(DimePayWebhookReceived::class, function ($event) {
        return $event->type === 'payment.success'
            && $event->payload['amount'] === 5000;
    });
});

it('DimePayWebhookReceived defaults type to unknown when missing', function () {
    Event::fake();

    $payload = ['amount' => 5000];
    $webhookCall = Mockery::mock(WebhookCall::class);
    $webhookCall->shouldReceive('getAttribute')->with('payload')->andReturn($payload);

    $job = new ProcessDimePayWebhookJob($webhookCall);
    $job->handle();

    Event::assertDispatched(DimePayWebhookReceived::class, function ($event) {
        return $event->type === 'unknown';
    });
});
