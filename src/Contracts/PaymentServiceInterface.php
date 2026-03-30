<?php

declare(strict_types=1);

namespace Osoobe\DimePay\Contracts;

use Osoobe\DimePay\Data\Orders\CreateOrderData;
use Osoobe\DimePay\Data\Payments\DirectPaymentData;
use Osoobe\DimePay\Data\Payments\HostedPageResponseData;
use Osoobe\DimePay\Data\Payments\PaymentResponseData;

interface PaymentServiceInterface
{
    public function hostedPage(CreateOrderData $data): HostedPageResponseData;

    public function authorize(DirectPaymentData $data): PaymentResponseData;

    public function sale(DirectPaymentData $data): PaymentResponseData;

    public function capture(string $transactionId): PaymentResponseData;

    public function void(string $transactionId): PaymentResponseData;

    public function refund(string $transactionId): PaymentResponseData;
}
