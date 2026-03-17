<?php

declare(strict_types=1);

namespace Osoobe\DimePay\Data\Orders;

use Osoobe\DimePay\Data\Shared\SelectedOptionData;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class OrderItemData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public int $price,
        public int $quantity,
        public string $sku,
        public ?string $shortDescription = null,
        public ?string $imageUrl = null,
        public ?string $merchantId = null,
        #[DataCollectionOf(SelectedOptionData::class)]
        public ?DataCollection $selectedOptions = null,
    ) {}
}
