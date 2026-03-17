<?php

declare(strict_types=1);

namespace Osoobe\DimePay;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class DimePayServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('dimepay')
            ->hasConfigFile();
    }
}
