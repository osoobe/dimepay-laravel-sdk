<?php

declare(strict_types=1);

namespace Osoobe\DimePay\Contracts;

use Osoobe\DimePay\Data\Orders\CreateOrderData;
use Osoobe\DimePay\Data\Orders\CreateOrderResponseData;
use Osoobe\DimePay\Data\Orders\OrderResponseData;

interface OrderServiceInterface
{
    public function create(CreateOrderData $data): CreateOrderResponseData;

    public function find(string $token): OrderResponseData;
}
