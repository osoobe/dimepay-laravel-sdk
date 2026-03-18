<?php

declare(strict_types=1);

use Osoobe\DimePay\Exceptions\DimePayAuthException;
use Osoobe\DimePay\Exceptions\DimePayException;
use Osoobe\DimePay\Exceptions\DimePayNotFoundException;
use Osoobe\DimePay\Exceptions\DimePayServerException;
use Osoobe\DimePay\Exceptions\DimePayValidationException;

it('creates auth exception with correct properties', function () {
    $e = DimePayAuthException::fromResponse(401, 'unauthorized', 'Invalid key', ['hint' => 'check key']);

    expect($e->getStatus())->toBe(401);
    expect($e->getErrorCode())->toBe('unauthorized');
    expect($e->getMessage())->toBe('Invalid key');
    expect($e->getDetails())->toBe(['hint' => 'check key']);
});

it('creates validation exception with correct properties', function () {
    $e = DimePayValidationException::fromResponse(400, 'bad_request', 'Invalid payload', []);

    expect($e->getStatus())->toBe(400);
    expect($e->getErrorCode())->toBe('bad_request');
});

it('creates not found exception with correct properties', function () {
    $e = DimePayNotFoundException::fromResponse(404, 'not_found', 'Order not found', []);

    expect($e->getStatus())->toBe(404);
    expect($e->getErrorCode())->toBe('not_found');
});

it('creates server exception with correct properties', function () {
    $e = DimePayServerException::fromResponse(500, 'server_error', 'Internal error', []);

    expect($e->getStatus())->toBe(500);
    expect($e->getErrorCode())->toBe('server_error');
});

it('all typed exceptions extend base DimePayException', function () {
    expect(new DimePayAuthException)->toBeInstanceOf(DimePayException::class);
    expect(new DimePayValidationException)->toBeInstanceOf(DimePayException::class);
    expect(new DimePayNotFoundException)->toBeInstanceOf(DimePayException::class);
    expect(new DimePayServerException)->toBeInstanceOf(DimePayException::class);
});
