<?php

declare(strict_types=1);

namespace Osoobe\DimePay\Contracts;

interface DimePayClientInterface
{
    public function get(string $endpoint, ?string $token = null): array;

    public function post(string $endpoint, array $payload = [], string $lang = 'en'): array;

    public function put(string $endpoint, array $payload = [], string $lang = 'en'): array;

    public function withConfig(array $config): static;
}
