<?php

declare(strict_types=1);

namespace Osoobe\DimePay\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Osoobe\DimePay\Exceptions\DimePayException;

class DimePayRequestFailed
{
    use Dispatchable;

    public function __construct(
        public readonly string $endpoint,
        public readonly array $payload,
        public readonly DimePayException $exception,
    ) {}
}
