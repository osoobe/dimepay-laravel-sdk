<?php

declare(strict_types=1);

namespace Osoobe\DimePay\Facades;

use Illuminate\Support\Facades\Facade;
use Osoobe\DimePay\Contracts\CardServiceInterface;
use Osoobe\DimePay\Contracts\OrderServiceInterface;
use Osoobe\DimePay\Contracts\PaymentServiceInterface;
use Osoobe\DimePay\DimePayManager;
use Osoobe\DimePay\Support\JwtSigner;

/**
 * @method static OrderServiceInterface orders()
 * @method static PaymentServiceInterface payments()
 * @method static CardServiceInterface cards()
 * @method static JwtSigner jwt()
 * @method static DimePayManager withConfig(array $config)
 *
 * @see DimePayManager
 */
class DimePay extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DimePayManager::class;
    }
}
