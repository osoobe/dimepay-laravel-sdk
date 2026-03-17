<?php

declare(strict_types=1);

namespace Osoobe\DimePay\Services;

use Osoobe\DimePay\Contracts\DimePayClientInterface;
use Osoobe\DimePay\Contracts\PaymentServiceInterface;
use Osoobe\DimePay\Data\Orders\CreateOrderData;
use Osoobe\DimePay\Data\Payments\DirectPaymentData;
use Osoobe\DimePay\Data\Payments\HostedPageResponseData;
use Osoobe\DimePay\Data\Payments\PaymentResponseData;

class PaymentService implements PaymentServiceInterface
{
    public function __construct(
        private readonly DimePayClientInterface $client,
    ) {}

    public function hostedPage(CreateOrderData $data): HostedPageResponseData
    {
        $response = $this->client->post('/payments/hosted-page', $data->toArray());

        return HostedPageResponseData::from($response);
    }

    public function authorize(DirectPaymentData $data): PaymentResponseData
    {
        $response = $this->client->post('/payments/auth', $data->toArray());

        return PaymentResponseData::from($response);
    }

    public function sale(DirectPaymentData $data): PaymentResponseData
    {
        $response = $this->client->post('/payments/sale', $data->toArray());

        return PaymentResponseData::from($response);
    }

    public function capture(string $transactionId): PaymentResponseData
    {
        $response = $this->client->put('/payments/capture', [
            'transaction_id' => $transactionId,
        ]);

        return PaymentResponseData::from($response);
    }

    public function void(string $transactionId): PaymentResponseData
    {
        $response = $this->client->put('/payments/void', [
            'transaction_id' => $transactionId,
        ]);

        return PaymentResponseData::from($response);
    }

    public function refund(string $transactionId): PaymentResponseData
    {
        $response = $this->client->put('/payments/refund', [
            'transaction_id' => $transactionId,
        ]);

        return PaymentResponseData::from($response);
    }
}
