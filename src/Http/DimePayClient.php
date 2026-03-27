<?php

declare(strict_types=1);

namespace Osoobe\DimePay\Http;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Osoobe\DimePay\Contracts\DimePayClientInterface;
use Osoobe\DimePay\Exceptions\DimePayAuthException;
use Osoobe\DimePay\Exceptions\DimePayException;
use Osoobe\DimePay\Exceptions\DimePayNotFoundException;
use Osoobe\DimePay\Exceptions\DimePayServerException;
use Osoobe\DimePay\Exceptions\DimePayValidationException;
use Osoobe\DimePay\Support\JwtSigner;

class DimePayClient implements DimePayClientInterface
{
    private array $config;

    private JwtSigner $signer;

    public function __construct(array $config = [])
    {
        $this->config = $config ?: config('dimepay');
        $this->signer = new JwtSigner($this->config);
    }

    public function get(string $endpoint, ?string $token = null): array
    {
        $url = $token
            ? $this->url($endpoint.'/'.$this->signer->signString($token))
            : $this->url($endpoint);

        $response = $this->makeRequest()->get($url);

        return $this->handle($response);
    }

    public function post(string $endpoint, array $payload = [], string $lang = 'en'): array
    {
        $response = $this->makeRequest()->post(
            $this->url($endpoint),
            $this->wrap($payload, $lang),
        );

        return $this->handle($response);
    }

    public function put(string $endpoint, array $payload = [], string $lang = 'en'): array
    {
        $response = $this->makeRequest()->put(
            $this->url($endpoint),
            $this->wrap($payload, $lang),
        );

        return $this->handle($response);
    }

    private function wrap(array $payload, string $lang): array
    {
        return [
            'lang' => $lang,
            'data' => $this->signer->sign($payload),
        ];
    }

    private function makeRequest(): PendingRequest
    {
        return Http::withHeaders([
            'client_key' => $this->config['client_key'],
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])
            ->timeout($this->config['timeout'] ?? 30)
            ->retry(
                times: $this->config['retries'] ?? 2,
                sleepMilliseconds: $this->config['retry_delay'] ?? 500,
                when: fn (\Exception $e) => $e instanceof RequestException && $e->response->serverError(),
                throw: false,
            );
    }

    private function url(string $endpoint): string
    {
        $env = $this->config['environment'] ?? 'sandbox';
        $base = $this->config['base_urls'][$env] ?? $this->config['base_urls']['sandbox'];

        return rtrim($base, '/').'/'.ltrim($endpoint, '/');
    }

    private function handle(Response $response): array
    {
        $this->log($response);

        $json = $response->json() ?? [];

        if ($response->successful()) {
            // DimePay wraps responses as { statusCode, body: { ... } }
            // Unwrap body if present, otherwise return raw json
            return $json['body']['response'] ?? $json['body'] ?? $json;
        }

        $this->throwException($response, $json);
    }

    private function throwException(Response $response, array $json): never
    {
        $status = $response->status();

        // DimePay error structure: { statusCode, body: { message: [], response: null } }
        $body = $json['body'] ?? $json;
        $message = $body['message'] ?? 'An unknown error occurred.';

        // message can be an array of validation errors or a string
        if (is_array($message)) {
            $details = $message;
            $message = implode(', ', $message);
        } else {
            $details = [];
        }

        $code = $body['code'] ?? $body['error'] ?? 'unknown_error';

        throw match ($status) {
            401 => DimePayAuthException::fromResponse($status, $code, $message, $details),
            400 => DimePayValidationException::fromResponse($status, $code, $message, $details),
            404 => DimePayNotFoundException::fromResponse($status, $code, $message, $details),
            500 => DimePayServerException::fromResponse($status, $code, $message, $details),
            default => DimePayException::fromResponse($status, $code, $message, $details),
        };
    }

    private function log(Response $response): void
    {
        if (!($this->config['logging']['enabled'] ?? false)) {
            return;
        }

        $channel = $this->config['logging']['channel'] ?? 'stack';
        $level = $this->config['logging']['level'] ?? 'debug';

        Log::channel($channel)->$level('DimePay API Response', [
            'status' => $response->status(),
            'body' => $response->json(),
        ]);
    }

    public function withConfig(array $config): static
    {
        $clone = clone $this;
        $clone->config = array_merge($this->config, $config);
        $clone->signer = new JwtSigner($clone->config);

        return $clone;
    }
}
