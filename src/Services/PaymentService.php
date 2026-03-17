<?php

declare(strict_types=1);

namespace Osoobe\DimePay\Services;

use Osoobe\DimePay\Contracts\DimePayClientInterface;
use Osoobe\DimePay\Contracts\PaymentServiceInterface;
use Osoobe\DimePay\Data\Orders\CreateOrderData;
use Osoobe\DimePay\Data\Payments\DirectPaymentData;
use Osoobe\DimePay\Data\Payments\HostedPageResponseData;
use Osoobe\DimePay\Data\Payments\PaymentResponseData;
use Osoobe\DimePay\Events\HostedPaymentPageCreated;
use Osoobe\DimePay\Events\PaymentAuthorized;
use Osoobe\DimePay\Events\PaymentCaptured;
use Osoobe\DimePay\Events\PaymentRefunded;
use Osoobe\DimePay\Events\PaymentSaleProcessed;
use Osoobe\DimePay\Events\PaymentVoided;

class PaymentService implements PaymentServiceInterface
{
    public function __construct(
        private readonly DimePayClientInterface $client,
    ) {}

    public function hostedPage(CreateOrderData $data): HostedPageResponseData
    {
        $response = $this->client->post('/payments/hosted-page', $data->toArray());
        $result   = HostedPageResponseData::from($response);

        event(new HostedPaymentPageCreated($data, $result));

        return $result;
    }

    public function authorize(DirectPaymentData $data): PaymentResponseData
    {
        $response = $this->client->post('/payments/auth', $data->toArray());
        $result   = PaymentResponseData::from($response);

        event(new PaymentAuthorized($data, $result));

        return $result;
    }

    public function sale(DirectPaymentData $data): PaymentResponseData
    {
        $response = $this->client->post('/payments/sale', $data->toArray());
        $result   = PaymentResponseData::from($response);

        event(new PaymentSaleProcessed($data, $result));

        return $result;
    }

    public function capture(string $transactionId): PaymentResponseData
    {
        $response = $this->client->put('/payments/capture', [
            'transaction_id' => $transactionId,
        ]);
        $result = PaymentResponseData::from($response);

        event(new PaymentCaptured($transactionId, $result));

        return $result;
    }

    public function void(string $transactionId): PaymentResponseData
    {
        $response = $this->client->put('/payments/void', [
            'transaction_id' => $transactionId,
        ]);
        $result = PaymentResponseData::from($response);

        event(new PaymentVoided($transactionId, $result));

        return $result;
    }

    public function refund(string $transactionId): PaymentResponseData
    {
        $response = $this->client->put('/payments/refund', [
            'transaction_id' => $transactionId,
        ]);
        $result = PaymentResponseData::from($response);

        event(new PaymentRefunded($transactionId, $result));

        return $result;
    }
}
