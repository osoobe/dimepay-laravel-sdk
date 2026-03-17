<?php

declare(strict_types=1);

namespace Osoobe\DimePay;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Osoobe\DimePay\Http\DimePayClient;
use Osoobe\DimePay\Support\JwtSigner;
use Osoobe\DimePay\Contracts\DimePayClientInterface;
use Osoobe\DimePay\Contracts\CardServiceInterface;
use Osoobe\DimePay\Contracts\OrderServiceInterface;
use Osoobe\DimePay\Contracts\PaymentServiceInterface;
use Osoobe\DimePay\Contracts\TransactionServiceInterface;

class DimePayServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('dimepay')
            ->hasConfigFile();
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(DimePayClient::class, function () {
            return new DimePayClient(config('dimepay'));
        });

        $this->app->singleton(JwtSigner::class, function () {
            return new JwtSigner(config('dimepay'));
        });

        $this->app->singletonIf(OrderServiceInterface::class);
        $this->app->singletonIf(PaymentServiceInterface::class);
        $this->app->singletonIf(CardServiceInterface::class);
        $this->app->singletonIf(TransactionServiceInterface::class);
    }
}
