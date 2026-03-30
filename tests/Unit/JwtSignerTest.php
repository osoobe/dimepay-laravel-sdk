<?php

declare(strict_types=1);

use Osoobe\DimePay\Exceptions\DimePayException;
use Osoobe\DimePay\Support\JwtSigner;

function makeSigner(): JwtSigner
{
    return new JwtSigner(sandboxConfig());
}

it('signs a payload and returns a jwt string', function () {
    $token = makeSigner()->sign(['id' => 'ORDER-001']);

    expect($token)
        ->toBeString()
        ->toContain('.');
});

it('decoded token contains original payload fields', function () {
    $signer = makeSigner();
    $token = $signer->sign(['id' => 'ORDER-001', 'total' => 5000]);
    $decoded = $signer->decode($token);

    expect($decoded['id'])->toBe('ORDER-001');
    expect($decoded['total'])->toBe(5000);
});

it('decoded token contains iat and exp claims', function () {
    $signer = makeSigner();
    $token = $signer->sign(['id' => 'ORDER-001']);
    $decoded = $signer->decode($token);

    expect($decoded)->toHaveKeys(['iat', 'exp']);
});

it('throws DimePayException when secret_key is missing', function () {
    $signer = new JwtSigner(sandboxConfig(['secret_key' => '']));

    expect(fn () => $signer->sign(['id' => 'ORDER-001']))
        ->toThrow(DimePayException::class);
});

it('throws when decoding with wrong secret', function () {
    $token = makeSigner()->sign(['id' => 'ORDER-001']);
    $wrongSigner = new JwtSigner(sandboxConfig(['secret_key' => 'wrong-secret']));

    expect(fn () => $wrongSigner->decode($token))
        ->toThrow(Exception::class);
});
