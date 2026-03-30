<?php

declare(strict_types=1);

namespace Osoobe\DimePay\Exceptions;

use Exception;

class DimePayException extends Exception
{
    protected string $errorCode = '';

    protected array $details = [];

    protected int $status = 0;

    public static function fromResponse(int $status, string $errorCode, string $message, array $details = []): static
    {
        $instance = new static($message);
        $instance->status = $status;
        $instance->errorCode = $errorCode;
        $instance->details = $details;

        return $instance;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getDetails(): array
    {
        return $this->details;
    }

    public function getStatus(): int
    {
        return $this->status;
    }
}
