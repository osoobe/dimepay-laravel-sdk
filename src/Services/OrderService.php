<?php

declare(strict_types=1);

namespace Osoobe\DimePay\Services;

use Osoobe\DimePay\Contracts\DimePayClientInterface;
use Osoobe\DimePay\Contracts\OrderServiceInterface;
use Osoobe\DimePay\Data\Orders\CreateOrderData;
use Osoobe\DimePay\Data\Orders\CreateOrderResponseData;
use Osoobe\DimePay\Data\Orders\OrderResponseData;
use Osoobe\DimePay\Events\OrderCreated;

class OrderService implements OrderServiceInterface
{
    public function __construct(
        private readonly DimePayClientInterface $client,
    ) {}

    public function create(CreateOrderData $data): CreateOrderResponseData
    {
        $response = $this->client->post('/orders', $data->toArray());
        $result   = CreateOrderResponseData::from($response);

        event(new OrderCreated($data, $result));

        return $result;
    }

    public function find(string $token): OrderResponseData
    {
        $response = $this->client->get('/orders', $token);

        return OrderResponseData::from($response);
    }
}
