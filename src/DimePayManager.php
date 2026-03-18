<?php

declare(strict_types=1);

namespace Osoobe\DimePay;

use Illuminate\Contracts\Container\Container;
use Osoobe\DimePay\Contracts\CardServiceInterface;
use Osoobe\DimePay\Contracts\DimePayClientInterface;
use Osoobe\DimePay\Contracts\OrderServiceInterface;
use Osoobe\DimePay\Contracts\PaymentServiceInterface;
use Osoobe\DimePay\Services\CardService;
use Osoobe\DimePay\Services\OrderService;
use Osoobe\DimePay\Services\PaymentService;
use Osoobe\DimePay\Support\JwtSigner;

class DimePayManager
{
    private ?array $configOverride = null;

    public function __construct(
        private readonly Container $app,
    ) {}

    public function orders(): OrderServiceInterface
    {
        if ($this->configOverride) {
            return new OrderService($this->makeClient());
        }

        return $this->app->make(OrderServiceInterface::class);
    }

    public function payments(): PaymentServiceInterface
    {
        if ($this->configOverride) {
            return new PaymentService($this->makeClient());
        }

        return $this->app->make(PaymentServiceInterface::class);
    }

    public function cards(): CardServiceInterface
    {
        if ($this->configOverride) {
            return new CardService($this->makeClient());
        }

        return $this->app->make(CardServiceInterface::class);
    }

    public function jwt(): JwtSigner
    {
        if ($this->configOverride) {
            return new JwtSigner($this->configOverride);
        }

        return $this->app->make(JwtSigner::class);
    }

    /**
     * Return a new manager instance with overridden config.
     * Used for multi-tenant scenarios where each tenant has
     * different DimePay credentials.
     */
    public function withConfig(array $config): static
    {
        $clone = clone $this;
        $clone->configOverride = array_merge(config('dimepay'), $config);

        return $clone;
    }

    private function makeClient(): DimePayClientInterface
    {
        return $this->app->make(DimePayClientInterface::class)
            ->withConfig($this->configOverride);
    }
}
