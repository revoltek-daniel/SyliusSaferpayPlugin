<?php

declare(strict_types=1);

namespace CommerceWeavers\SyliusSaferpayPlugin\Client;

use CommerceWeavers\SyliusSaferpayPlugin\Provider\UuidProviderInterface;
use Payum\Core\Model\GatewayConfigInterface;
use Payum\Core\Security\TokenInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Webmozart\Assert\Assert;

final class SaferpayClientBodyFactory implements SaferpayClientBodyFactoryInterface
{
    private const SPEC_VERSION = '1.33';

    public function __construct(
        private UuidProviderInterface $uuidProvider,
    ) {
    }

    public function createForAuthorize(PaymentInterface $payment, TokenInterface $token): array
    {
        $order = $payment->getOrder();
        Assert::notNull($order);
        /** @var string $orderNumber */
        $orderNumber = $order->getNumber();

        $gatewayConfig = $this->provideGatewayConfig($payment);
        $config = $gatewayConfig->getConfig();
        $terminalId = (string) $config['terminal_id'];
        /** @var array $allowedPaymentMethods */
        $allowedPaymentMethods = $config['allowed_payment_methods'] ?? [];

        return array_merge($this->provideBodyRequestHeader($gatewayConfig), [
            'TerminalId' => $terminalId,
            'Payment' => [
                'Amount' => [
                    'Value' => $payment->getAmount(),
                    'CurrencyCode' => $payment->getCurrencyCode(),
                ],
                'OrderId' => $orderNumber,
                'Description' => sprintf('Payment for order #%s', $orderNumber),
            ],
            'PaymentMethods' => array_values($allowedPaymentMethods),
            'Notification' => [
                'PayerEmail' => $payment->getOrder()?->getCustomer()?->getEmail(),
            ],
            'ReturnUrl' => [
                'Url' => $token->getAfterUrl(),
            ],
        ]);
    }

    public function createForAssert(PaymentInterface $payment): array
    {
        return array_merge($this->provideBodyRequestHeader($this->provideGatewayConfig($payment)), [
            'Token' => $payment->getDetails()['saferpay_token'],
        ]);
    }

    public function createForCapture(PaymentInterface $payment): array
    {
        return array_merge($this->provideBodyRequestHeader($this->provideGatewayConfig($payment)), [
            'TransactionReference' => [
                'TransactionId' => $payment->getDetails()['transaction_id'],
            ],
        ]);
    }

    public function createForRefund(PaymentInterface $payment): array
    {
        return array_merge($this->provideBodyRequestHeader($this->provideGatewayConfig($payment)), [
            'Refund' => [
                'Amount' => [
                    'Value' => $payment->getAmount(),
                    'CurrencyCode' => $payment->getCurrencyCode(),
                ],
            ],
            'CaptureReference' => [
                'CaptureId' => $payment->getDetails()['capture_id'],
            ],
        ]);
    }

    public function provideHeadersForTerminal(): array
    {
        return [
            'Saferpay-ApiVersion' => self::SPEC_VERSION,
            'Saferpay-RequestId' => $this->uuidProvider->provide(),
        ];
    }

    private function provideBodyRequestHeader(GatewayConfigInterface $gatewayConfig): array
    {
        $customerId = (string) $gatewayConfig->getConfig()['customer_id'];

        return [
            'RequestHeader' => [
                'SpecVersion' => self::SPEC_VERSION,
                'CustomerId' => $customerId,
                'RequestId' => $this->uuidProvider->provide(),
                'RetryIndicator' => 0,
            ],
        ];
    }

    private function provideGatewayConfig(PaymentInterface $payment): GatewayConfigInterface
    {
        /** @var PaymentMethodInterface|null $paymentMethod */
        $paymentMethod = $payment->getMethod();
        Assert::notNull($paymentMethod);
        $gatewayConfig = $paymentMethod->getGatewayConfig();
        Assert::notNull($gatewayConfig);

        return $gatewayConfig;
    }
}
