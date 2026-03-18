<?php

declare(strict_types=1);

namespace Osoobe\DimePay\Data\Orders;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;

final class OrderItemData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public float $price,
        public int $quantity,
        public string $sku,
        public string $shortDescription = '',
        public string $imageUrl = 'https://example.com/image.jpg',
        #[MapOutputName('merchant_id')]
        public ?string $merchantId = null,
        public array $selectedOptions = [],
    ) {}
}
