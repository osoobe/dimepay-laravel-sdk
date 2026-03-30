<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Osoobe\DimePay\Data\Cards\CardData;
use Osoobe\DimePay\Data\Cards\CardRequestData;
use Osoobe\DimePay\Data\Cards\CardRequestResponseData;
use Osoobe\DimePay\Events\CardTokenRequested;
use Osoobe\DimePay\Exceptions\DimePayAuthException;
use Osoobe\DimePay\Facades\DimePay;

function makeCardRequestData(): CardRequestData
{
    return new CardRequestData(
        id: 'CARD-REQ-001',
        webhookUrl: 'https://example.com/webhook',
        redirectUrl: 'https://example.com/callback',
    );
}

function cardRequestResponse(): array
{
    return [
        'token' => 'cr_abc123',
        'cardUrl' => 'https://sandbox.dimepay.app/e-card/abc123',
        'referenceId' => 'CARD-REQ-001',
        'status' => 'SUCCESS',
        'expired' => false,
        'currency' => 'JMD',
        'cardExpiry' => '12/25',
        'cardScheme' => 'Visa',
        'lastFourDigits' => '1111',
        'verificationAttempts' => 0,
    ];
}

it('requests a card token and returns CardRequestResponseData', function () {
    Http::fake([
        '*/card-request' => Http::response(cardRequestResponse(), 201),
    ]);

    $response = DimePay::cards()->requestToken(makeCardRequestData());

    expect($response)->toBeInstanceOf(CardRequestResponseData::class);
    expect($response->token)->toBe('cr_abc123');
    expect($response->status)->toBe('SUCCESS');
});

it('fires CardTokenRequested event', function () {
    Event::fake();
    Http::fake(['*/card-request' => Http::response(cardRequestResponse(), 201)]);

    DimePay::cards()->requestToken(makeCardRequestData());

    Event::assertDispatched(CardTokenRequested::class);
});

it('finds a saved card by token and returns CardData', function () {
    Http::fake([
        '*/cards/*' => Http::response([
            'token' => 'card_abc123',
            'cardRequestToken' => 'cr_abc123',
            'referenceId' => 'CARD-REQ-001',
            'status' => 'SUCCESS',
            'expired' => false,
            'verificationAttempts' => 0,
        ], 200),
    ]);

    $response = DimePay::cards()->find('cr_abc123');

    expect($response)->toBeInstanceOf(CardData::class);
    expect($response->token)->toBe('card_abc123');
});

it('throws DimePayAuthException on 401', function () {
    Http::fake([
        '*/card-request' => Http::response(['code' => 'unauthorized', 'message' => 'Invalid key', 'details' => []], 401),
    ]);

    expect(fn () => DimePay::cards()->requestToken(makeCardRequestData()))
        ->toThrow(DimePayAuthException::class);
});
