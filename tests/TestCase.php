<?php

declare(strict_types=1);

namespace Osoobe\DimePay\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Osoobe\DimePay\DimePayServiceProvider;
use Osoobe\DimePay\Facades\DimePay;
use Spatie\LaravelData\LaravelDataServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            LaravelDataServiceProvider::class,
            DimePayServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'DimePay' => DimePay::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('dimepay', sandboxConfig());
    }
}
