<?php

declare(strict_types=1);

namespace Osoobe\DimePay\Data\Shared;

use Spatie\LaravelData\Data;

final class PersonData extends Data
{
    public function __construct(
        public ?string $name,
        public ?string $street,
        public ?string $city,
        public ?string $stateOrProvinceName,
        public ?string $postalCode,
        public ?string $countryName,
        public ?string $email = null,
        public ?string $companyName = null,
        public ?string $countryCode = null,
        public ?string $stateOrProvinceCode = null,
        public ?string $phone = null,
    ) {}
}
