<?php

declare(strict_types=1);

namespace Osoobe\DimePay\Data\Shared;

use Spatie\LaravelData\Data;

final class CustomerData extends Data
{
    public function __construct(
        public string $name,
        public ?string $email = null,
        public ?string $phone = null,
    ) {}
}
