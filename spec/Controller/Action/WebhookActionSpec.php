<?php

declare(strict_types=1);

namespace spec\CommerceWeavers\SyliusSaferpayPlugin\Controller\Action;

use CommerceWeavers\SyliusSaferpayPlugin\Payment\Command\AssertPaymentCommand;
use CommerceWeavers\SyliusSaferpayPlugin\Processor\SaferpayPaymentProcessorInterface;
use CommerceWeavers\SyliusSaferpayPlugin\Provider\PaymentProviderInterface;
use Payum\Core\Payum;
use Payum\Core\Security\HttpRequestVerifierInterface;
use Payum\Core\Security\TokenInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Payment\Model\GatewayConfigInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class WebhookActionSpec extends ObjectBehavior
{
    function let(
        Payum $payum,
        MessageBusInterface $commandBus,
        LoggerInterface $logger,
        PaymentProviderInterface $paymentProvider,
        SaferpayPaymentProcessorInterface $saferpayPaymentProcessor,
    ): void {
        $this->beConstructedWith($payum, $commandBus, $logger, $paymentProvider, $saferpayPaymentProcessor);
    }

    function it_asserts_a_saferpay_payment(
        Payum $payum,
        MessageBusInterface $commandBus,
        PaymentProviderInterface $paymentProvider,
        SaferpayPaymentProcessorInterface $saferpayPaymentProcessor,
        HttpRequestVerifierInterface $httpRequestVerifier,
        TokenInterface $token,
        PaymentInterface $payment,
        PaymentMethodInterface $paymentMethod,
        GatewayConfigInterface $gatewayConfig,
    ): void {
        $request = new Request(attributes: ['order_token' => 'ORDER_TOKEN']);

        $paymentProvider->provideForOrder('ORDER_TOKEN')->willReturn($payment);
        $payment->getMethod()->willReturn($paymentMethod);
        $paymentMethod->getGatewayConfig()->willReturn($gatewayConfig);
        $gatewayConfig->getFactoryName()->willReturn('saferpay');

        $saferpayPaymentProcessor->lock($payment)->shouldBeCalled();

        $payum->getHttpRequestVerifier()->willReturn($httpRequestVerifier);
        $httpRequestVerifier->verify($request)->willReturn($token);
        $token->getHash()->willReturn('TOKEN_HASH');

        $commandBus
            ->dispatch(new AssertPaymentCommand('TOKEN_HASH'))
            ->willReturn(new Envelope(new \stdClass()))
            ->shouldBeCalled()
        ;

        $this($request)->getStatusCode()->shouldReturn(Response::HTTP_OK);
    }

    function it_ignores_the_webhook_if_the_payment_no_longer_uses_a_saferpay_payment_method(
        Payum $payum,
        MessageBusInterface $commandBus,
        LoggerInterface $logger,
        PaymentProviderInterface $paymentProvider,
        SaferpayPaymentProcessorInterface $saferpayPaymentProcessor,
        HttpRequestVerifierInterface $httpRequestVerifier,
        TokenInterface $token,
        PaymentInterface $payment,
        PaymentMethodInterface $paymentMethod,
        GatewayConfigInterface $gatewayConfig,
    ): void {
        $request = new Request(attributes: ['order_token' => 'ORDER_TOKEN']);

        $paymentProvider->provideForOrder('ORDER_TOKEN')->willReturn($payment);
        $payment->getId()->willReturn(42);
        $payment->getMethod()->willReturn($paymentMethod);
        $paymentMethod->getGatewayConfig()->willReturn($gatewayConfig);
        $gatewayConfig->getFactoryName()->willReturn('offline');

        $payum->getHttpRequestVerifier()->willReturn($httpRequestVerifier);
        $httpRequestVerifier->verify($request)->willReturn($token);
        $httpRequestVerifier->invalidate($token)->shouldBeCalled();

        $logger->debug(Argument::cetera())->shouldBeCalled();
        $logger->warning(Argument::containingString('payment 42'), Argument::any())->shouldBeCalled();

        $saferpayPaymentProcessor->lock(Argument::any())->shouldNotBeCalled();
        $commandBus->dispatch(Argument::any())->shouldNotBeCalled();

        $this($request)->getStatusCode()->shouldReturn(Response::HTTP_OK);
    }
}
