<?php

declare(strict_types=1);

namespace Osoobe\DimePay\Data\Cards;

use Spatie\LaravelData\Data;

final class CardRequestData extends Data
{
    public function __construct(
        public ?string $id,
        public ?string $webhookUrl,
        public ?string $redirectUrl = null,
    ) {}
}
