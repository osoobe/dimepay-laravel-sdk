<?php

declare(strict_types=1);

namespace Osoobe\DimePay\Http;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Osoobe\DimePay\Exceptions\DimePayAuthException;
use Osoobe\DimePay\Exceptions\DimePayException;
use Osoobe\DimePay\Exceptions\DimePayNotFoundException;
use Osoobe\DimePay\Exceptions\DimePayServerException;
use Osoobe\DimePay\Exceptions\DimePayValidationException;

class DimePayClient
{
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config ?: config('dimepay');
    }

    public function get(string $endpoint): array
    {
        $response = $this->makeRequest()->get($this->url($endpoint));

        return $this->handle($response);
    }

    public function post(string $endpoint, array $payload = []): array
    {
        $response = $this->makeRequest()->post($this->url($endpoint), $payload);

        return $this->handle($response);
    }

    public function put(string $endpoint, array $payload = []): array
    {
        $response = $this->makeRequest()->put($this->url($endpoint), $payload);

        return $this->handle($response);
    }

    private function makeRequest(): PendingRequest
    {
        $request = Http::withHeaders([
            'client_key' => $this->config['client_key'],
            'Accept'     => 'application/json',
            'Content-Type' => 'application/json',
        ])
        ->timeout($this->config['timeout'] ?? 30)
        ->retry(
            $this->config['retries'] ?? 2,
            $this->config['retry_delay'] ?? 500,
            fn (\Exception $e) => $e instanceof DimePayServerException,
        );

        return $request;
    }

    private function url(string $endpoint): string
    {
        $env = $this->config['environment'] ?? 'sandbox';
        $base = $this->config['base_urls'][$env] ?? $this->config['base_urls']['sandbox'];

        return rtrim($base, '/') . '/' . ltrim($endpoint, '/');
    }

    private function handle(Response $response): array
    {
        $this->log($response);

        if ($response->successful()) {
            return $response->json() ?? [];
        }

        $this->throwException($response);
    }

    private function throwException(Response $response): never
    {
        $body = $response->json() ?? [];
        $status = $response->status();
        $code = $body['code'] ?? 'unknown_error';
        $message = $body['message'] ?? 'An unknown error occurred.';
        $details = $body['details'] ?? [];

        throw match ($status) {
            401     => DimePayAuthException::fromResponse($status, $code, $message, $details),
            400     => DimePayValidationException::fromResponse($status, $code, $message, $details),
            404     => DimePayNotFoundException::fromResponse($status, $code, $message, $details),
            500     => DimePayServerException::fromResponse($status, $code, $message, $details),
            default => DimePayException::fromResponse($status, $code, $message, $details),
        };
    }

    private function log(Response $response): void
    {
        if (! ($this->config['logging']['enabled'] ?? false)) {
            return;
        }

        $channel = $this->config['logging']['channel'] ?? 'stack';
        $level = $this->config['logging']['level'] ?? 'debug';

        Log::channel($channel)->$level('DimePay API Response', [
            'status'  => $response->status(),
            'body'    => $response->json(),
        ]);
    }

    public function withConfig(array $config): static
    {
        $clone = clone $this;
        $clone->config = array_merge($this->config, $config);

        return $clone;
    }
}
