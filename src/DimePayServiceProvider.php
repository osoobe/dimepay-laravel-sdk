<?php

declare(strict_types=1);

namespace Osoobe\DimePay;

use Illuminate\Foundation\Console\AboutCommand;
use Osoobe\DimePay\Commands\InstallCommand;
use Osoobe\DimePay\Contracts\CardServiceInterface;
use Osoobe\DimePay\Contracts\DimePayClientInterface;
use Osoobe\DimePay\Contracts\OrderServiceInterface;
use Osoobe\DimePay\Contracts\PaymentServiceInterface;
use Osoobe\DimePay\Contracts\TransactionServiceInterface;
use Osoobe\DimePay\Http\DimePayClient;
use Osoobe\DimePay\Services\CardService;
use Osoobe\DimePay\Services\OrderService;
use Osoobe\DimePay\Services\PaymentService;
use Osoobe\DimePay\Services\TransactionService;
use Osoobe\DimePay\Support\JwtSigner;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class DimePayServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('dimepay')
            ->hasConfigFile()
            ->hasCommand(InstallCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(JwtSigner::class, function () {
            return new JwtSigner(config('dimepay'));
        });

        $this->app->singleton(DimePayClientInterface::class, function () {
            return new DimePayClient(config('dimepay'));
        });

        $this->app->singletonIf(OrderServiceInterface::class, function ($app) {
            return new OrderService($app->make(DimePayClientInterface::class));
        });

        $this->app->singletonIf(PaymentServiceInterface::class, function ($app) {
            return new PaymentService($app->make(DimePayClientInterface::class));
        });

        $this->app->singletonIf(CardServiceInterface::class, function ($app) {
            return new CardService($app->make(DimePayClientInterface::class));
        });

        $this->app->singletonIf(TransactionServiceInterface::class, function () {
            return new TransactionService();
        });

        $this->app->singleton(DimePayManager::class, function ($app) {
            return new DimePayManager($app);
        });
    }

    public function packageBooted(): void
    {
        if (config('dimepay.routes.enabled', true)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/dimepay.php');
        }

        AboutCommand::add('DimePay SDK', fn () => [
            'Version'     => '1.0.0',
            'Environment' => config('dimepay.environment', 'sandbox'),
            'Logging'     => config('dimepay.logging.enabled') ? 'enabled' : 'disabled',
        ]);
    }
}
