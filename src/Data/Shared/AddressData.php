<?php

declare(strict_types=1);

namespace Osoobe\DimePay\Data\Shared;

use Spatie\LaravelData\Data;

final class AddressData extends Data
{
    public function __construct(
        public string $street,
        public string $city,
        public string $stateOrProvinceName,
        public string $postalCode,
        public string $countryName,
    ) {}
}
