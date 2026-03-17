<?php

declare(strict_types=1);

namespace Osoobe\DimePay;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Osoobe\DimePay\Http\DimePayClient;

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
    }
}
