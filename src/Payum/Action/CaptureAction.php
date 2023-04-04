<?php

declare(strict_types=1);

namespace CommerceWeavers\SyliusSaferpayPlugin\Payum\Action;

use GuzzleHttp\ClientInterface;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\Capture;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;

final class CaptureAction implements ActionInterface
{
    public function __construct(private ClientInterface $client)
    {
    }

    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var SyliusPaymentInterface $payment */
        $payment = $request->getModel();

        $afterUrl = $request->getToken()->getAfterUrl();

        $json = sprintf('{
          "RequestHeader": {
            "SpecVersion": "1.33",
            "CustomerId": "268229",
            "RequestId": "331238af17-35c1-4165-a343-c1c86a320f3b",
            "RetryIndicator": 0
          },
            "TerminalId": "17757531",
          "Payment": {
            "Amount": {
              "Value": "100",
              "CurrencyCode": "EUR"
            },
            "OrderId": "00000001",
            "Description": "Description of payment"
          },
          "ReturnUrl": {
            "Url": "%s"
          }
        }', $afterUrl);

        $response = $this->client->request('POST', 'https://test.saferpay.com/api/Payment/v1/PaymentPage/Initialize', [
            'body' => $json,
            'headers' => [
                'Authorization' => 'Basic QVBJXzI2ODIyOV8yNDQyMDU5OTpKc29uQXBpUHdkMV82RjRjU2IsKGk9KVc=',
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]
        ]);

        $request->getToken()->setAfterUrl(json_decode($response->getBody()->getContents(), true)['RedirectUrl']);

        $payment->setDetails(['status' => 200]);
    }

    public function supports($request): bool
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof SyliusPaymentInterface
        ;
    }
}
