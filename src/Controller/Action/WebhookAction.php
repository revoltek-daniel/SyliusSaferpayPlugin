<?php

declare(strict_types=1);

namespace CommerceWeavers\SyliusSaferpayPlugin\Controller\Action;

use CommerceWeavers\SyliusSaferpayPlugin\Exception\PaymentAlreadyProcessedException;
use CommerceWeavers\SyliusSaferpayPlugin\Exception\PaymentBeingProcessedException;
use CommerceWeavers\SyliusSaferpayPlugin\Payment\Command\AssertPaymentCommand;
use CommerceWeavers\SyliusSaferpayPlugin\Processor\SaferpayPaymentProcessorInterface;
use CommerceWeavers\SyliusSaferpayPlugin\Provider\PaymentProviderInterface;
use Payum\Core\Payum;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;

final class WebhookAction
{
    private const SAFERPAY_FACTORY_NAME = 'saferpay';

    public function __construct(
        private Payum $payum,
        private MessageBusInterface $commandBus,
        private LoggerInterface $logger,
        private PaymentProviderInterface $paymentProvider,
        private SaferpayPaymentProcessorInterface $saferpayPaymentProcessor,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        /** @var string $orderToken */
        $orderToken = $request->attributes->get('order_token');
        $this->logger->debug('Handling webhook started for order ' . $orderToken);

        $payment = $this->paymentProvider->provideForOrder($orderToken);

        // The customer may have switched to another payment method after starting the Saferpay payment
        if ($this->isSaferpayPayment($payment) === false) {
            $httpRequestVerifier = $this->payum->getHttpRequestVerifier();
            $httpRequestVerifier->invalidate($httpRequestVerifier->verify($request));

            $this->logger->warning(
                'Webhook ignored - payment ' . (string) $payment->getId() . ' no longer uses a Saferpay payment method',
                ['orderToken' => $orderToken],
            );

            return new JsonResponse(status: Response::HTTP_OK);
        }

        try {
            $this->saferpayPaymentProcessor->lock($payment);
        } catch (PaymentBeingProcessedException|PaymentAlreadyProcessedException) {
            $this->logger->debug('Webhook aborted - payment ' . $orderToken . ' already processed');

            return new JsonResponse(status: Response::HTTP_OK);
        }

        $token = $this->payum->getHttpRequestVerifier()->verify($request);

        try {
            $this->commandBus->dispatch(new AssertPaymentCommand($token->getHash()));
        } catch (HandlerFailedException $exception) {
            $this->logger->debug('Webhook failed: ' . $orderToken, ['exception' => $exception->getMessage()]);

            return new JsonResponse(status: Response::HTTP_BAD_REQUEST);
        }

        $this->logger->debug('Webhook handled ' . $orderToken . ' successfully');

        return new JsonResponse(status: Response::HTTP_OK);
    }

    private function isSaferpayPayment(PaymentInterface $payment): bool
    {
        /** @var PaymentMethodInterface|null $paymentMethod */
        $paymentMethod = $payment->getMethod();

        return $paymentMethod?->getGatewayConfig()?->getFactoryName() === self::SAFERPAY_FACTORY_NAME;
    }
}
