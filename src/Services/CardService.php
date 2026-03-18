<?php

declare(strict_types=1);

namespace Osoobe\DimePay\Services;

use Osoobe\DimePay\Contracts\CardServiceInterface;
use Osoobe\DimePay\Contracts\DimePayClientInterface;
use Osoobe\DimePay\Data\Cards\CardData;
use Osoobe\DimePay\Data\Cards\CardRequestData;
use Osoobe\DimePay\Data\Cards\CardRequestResponseData;
use Osoobe\DimePay\Events\CardTokenRequested;

class CardService implements CardServiceInterface
{
    public function __construct(
        private readonly DimePayClientInterface $client,
    ) {}

    public function requestToken(CardRequestData $data): CardRequestResponseData
    {
        $response = $this->client->post('/card-request', $data->toArray());
        $result = CardRequestResponseData::from($response);

        event(new CardTokenRequested($data, $result));

        return $result;
    }

    public function find(string $cardRequestToken): CardData
    {
        $response = $this->client->get('/cards/'.$cardRequestToken);

        return CardData::from($response);
    }
}
