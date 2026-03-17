<?php

declare(strict_types=1);

namespace Osoobe\DimePay\Data\Shared;

use Spatie\LaravelData\Data;

final class SelectedOptionData extends Data
{
    public function __construct(
        public string $name,
        public string $value,
        public string $type,
    ) {}
}
