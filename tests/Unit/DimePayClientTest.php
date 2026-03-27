<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Osoobe\DimePay\Exceptions\DimePayAuthException;
use Osoobe\DimePay\Exceptions\DimePayNotFoundException;
use Osoobe\DimePay\Exceptions\DimePayServerException;
use Osoobe\DimePay\Exceptions\DimePayValidationException;
use Osoobe\DimePay\Http\DimePayClient;

function makeClient(): DimePayClient
{
    return new DimePayClient(sandboxConfig());
}

it('sends client_key header on every request', function () {
    Http::fake(['*' => Http::response(['order_url' => 'https://sandbox.dimepay.app/e-order/test'], 200)]);

    makeClient()->post('/orders', ['id' => 'ORDER-001']);

    Http::assertSent(function ($request) {
        return $request->header('client_key')[0] === 'ck_test_dimepay_sdk_unit_tests';
    });
});

it('wraps post payload as signed jwt with lang and data fields', function () {
    Http::fake(['*' => Http::response(['order_url' => 'https://sandbox.dimepay.app/e-order/test'], 200)]);

    makeClient()->post('/orders', ['id' => 'ORDER-001']);

    Http::assertSent(function ($request) {
        $body = $request->data();

        return isset($body['lang']) && isset($body['data']);
    });
});

it('resolves sandbox base url from config', function () {
    Http::fake(['sandbox.api.dimepay.app/*' => Http::response(['order_url' => 'test'], 200)]);

    makeClient()->post('/orders', ['id' => 'ORDER-001']);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'sandbox.api.dimepay.app');
    });
});

it('resolves production base url when environment is production', function () {
    Http::fake(['api.dimepay.app/*' => Http::response(['order_url' => 'test'], 200)]);

    $client = new DimePayClient(sandboxConfig(['environment' => 'production']));
    $client->post('/orders', ['id' => 'ORDER-001']);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'api.dimepay.app')
            && !str_contains($request->url(), 'sandbox');
    });
});

it('throws DimePayAuthException on 401', function () {
    Http::fake(['*' => Http::response(['statusCode' => 401, 'body' => ['message' => 'Unauthorized', 'response' => null]], 401)]);

    expect(fn () => makeClient()->post('/orders', []))
        ->toThrow(DimePayAuthException::class);
});

it('throws DimePayValidationException on 400', function () {
    Http::fake(['*' => Http::response(['statusCode' => 400, 'body' => ['message' => ['Bad request'], 'response' => null]], 400)]);

    expect(fn () => makeClient()->post('/orders', []))
        ->toThrow(DimePayValidationException::class);
});

it('throws DimePayNotFoundException on 404', function () {
    Http::fake(['*' => Http::response(['statusCode' => 404, 'body' => ['message' => 'Not found', 'response' => null]], 404)]);

    expect(fn () => makeClient()->get('/orders', 'bad-token'))
        ->toThrow(DimePayNotFoundException::class);
});

it('throws DimePayServerException on 500', function () {
    Http::fake(['*' => Http::response(['statusCode' => 500, 'body' => ['message' => 'Server error', 'response' => null]], 500)]);

    expect(fn () => makeClient()->post('/orders', []))
        ->toThrow(DimePayServerException::class);
});

it('withConfig returns new instance with overridden config', function () {
    Http::fake(['*' => Http::response(['order_url' => 'test'], 200)]);

    makeClient()->withConfig(['client_key' => 'ck_new_key'])->post('/orders', []);

    Http::assertSent(function ($request) {
        return $request->header('client_key')[0] === 'ck_new_key';
    });
});
