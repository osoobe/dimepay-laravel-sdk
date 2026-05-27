<?php

declare(strict_types=1);

namespace Osoobe\DimePay\Data\Orders;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
final class CreateOrderResponseData extends Data
{
    public function __construct(
        public ?string $orderUrl,
    ) {}
}
