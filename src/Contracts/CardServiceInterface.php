<?php

declare(strict_types=1);

namespace Osoobe\DimePay\Contracts;

use Osoobe\DimePay\Data\Cards\CardData;
use Osoobe\DimePay\Data\Cards\CardRequestData;
use Osoobe\DimePay\Data\Cards\CardRequestResponseData;

interface CardServiceInterface
{
    public function requestToken(CardRequestData $data): CardRequestResponseData;

    public function find(string $cardRequestToken): CardData;
}
