<?php

declare(strict_types=1);

namespace Osoobe\DimePay\Support;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Osoobe\DimePay\Exceptions\DimePayException;

class JwtSigner
{
    private string $secretKey;

    private string $algorithm;

    private int $ttl;

    public function __construct(array $config = [])
    {
        $this->secretKey = $config['secret_key'] ?? '';
        $this->algorithm = $config['jwt']['algorithm'] ?? 'HS256';
        $this->ttl = $config['jwt']['ttl'] ?? 3600;
    }

    /**
     * Sign a payload and return a JWT string.
     */
    public function sign(array $payload): string
    {
        if (empty($this->secretKey)) {
            throw new DimePayException('DimePay secret_key is not set. Check your dimepay config.');
        }

        $now = time();

        $claims = array_merge($payload, [
            'iat' => $now,
            'exp' => $now + $this->ttl,
        ]);

        return JWT::encode($claims, $this->secretKey, $this->algorithm);
    }

    /**
     * Decode and verify a JWT string. Useful for testing / webhook verification.
     */
    public function decode(string $token): array
    {
        if (empty($this->secretKey)) {
            throw new DimePayException('DimePay secret_key is not set. Check your dimepay config.');
        }

        $decoded = JWT::decode($token, new Key($this->secretKey, $this->algorithm));

        return (array) $decoded;
    }
}
