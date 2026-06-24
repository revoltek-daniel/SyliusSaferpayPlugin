<?php

declare(strict_types=1);

namespace spec\CommerceWeavers\SyliusSaferpayPlugin\Controller\Action;

use CommerceWeavers\SyliusSaferpayPlugin\Provider\OrderProviderInterface;
use Doctrine\Common\Collections\ArrayCollection;
use PhpSpec\ObjectBehavior;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class AfterUnsuccessfulPaymentActionSpec extends ObjectBehavior
{
    function let(
        OrderProviderInterface $orderProvider,
        UrlGeneratorInterface $router,
        LoggerInterface $logger,
    ): void {
        $this->beConstructedWith($orderProvider, $router, $logger);
    }

    function it_redirects_to_order_show_page_and_flashes_cancelled_if_penultimate_payment_is_cancelled(
        OrderProviderInterface $orderProvider,
        UrlGeneratorInterface $router,
        Request $request,
        OrderInterface $order,
        PaymentInterface $lastPayment,
        PaymentInterface $penultimatePayment,
        Session $session,
        FlashBagInterface $flashBag,
    ): void {
        $orderProvider->provide('TOKEN')->willReturn($order);
        $order->getPayments()->willReturn(new ArrayCollection([
            $penultimatePayment->getWrappedObject(),
            $lastPayment->getWrappedObject(),
        ]));

        $lastPayment->getState()->willReturn(PaymentInterface::STATE_NEW);
        $penultimatePayment->getState()->willReturn(PaymentInterface::STATE_CANCELLED);

        $request->getSession()->willReturn($session);
        $session->getFlashBag()->willReturn($flashBag);
        $flashBag->add('error', 'sylius.payment.cancelled')->shouldBeCalled();

        $router->generate('sylius_shop_order_show', ['tokenValue' => 'TOKEN'])->willReturn('/orders/TOKEN');

        $this($request, 'TOKEN')->shouldBeLike(new RedirectResponse('/orders/TOKEN'));
    }

    function it_flashes_the_saferpay_payer_message_if_present(
        OrderProviderInterface $orderProvider,
        UrlGeneratorInterface $router,
        Request $request,
        OrderInterface $order,
        PaymentInterface $lastPayment,
        PaymentInterface $penultimatePayment,
        Session $session,
        FlashBagInterface $flashBag,
    ): void {
        $orderProvider->provide('TOKEN')->willReturn($order);
        $order->getPayments()->willReturn(new ArrayCollection([
            $penultimatePayment->getWrappedObject(),
            $lastPayment->getWrappedObject(),
        ]));

        $lastPayment->getState()->willReturn(PaymentInterface::STATE_NEW);
        $penultimatePayment->getState()->willReturn(PaymentInterface::STATE_FAILED);
        $penultimatePayment->getDetails()->willReturn([
            'payer_message' => 'Use another card or other payment method.',
        ]);

        $request->getSession()->willReturn($session);
        $session->getFlashBag()->willReturn($flashBag);
        $flashBag->add('error', 'Use another card or other payment method.')->shouldBeCalled();

        $router->generate('sylius_shop_order_show', ['tokenValue' => 'TOKEN'])->willReturn('/orders/TOKEN');

        $this($request, 'TOKEN')->shouldBeLike(new RedirectResponse('/orders/TOKEN'));
    }

    function it_falls_back_to_generic_failed_message_if_no_payer_message(
        OrderProviderInterface $orderProvider,
        UrlGeneratorInterface $router,
        Request $request,
        OrderInterface $order,
        PaymentInterface $lastPayment,
        PaymentInterface $penultimatePayment,
        Session $session,
        FlashBagInterface $flashBag,
    ): void {
        $orderProvider->provide('TOKEN')->willReturn($order);
        $order->getPayments()->willReturn(new ArrayCollection([
            $penultimatePayment->getWrappedObject(),
            $lastPayment->getWrappedObject(),
        ]));

        $lastPayment->getState()->willReturn(PaymentInterface::STATE_NEW);
        $penultimatePayment->getState()->willReturn(PaymentInterface::STATE_FAILED);
        $penultimatePayment->getDetails()->willReturn([]);

        $request->getSession()->willReturn($session);
        $session->getFlashBag()->willReturn($flashBag);
        $flashBag->add('error', 'sylius.payment.failed')->shouldBeCalled();

        $router->generate('sylius_shop_order_show', ['tokenValue' => 'TOKEN'])->willReturn('/orders/TOKEN');

        $this($request, 'TOKEN')->shouldBeLike(new RedirectResponse('/orders/TOKEN'));
    }

    function it_redirects_to_thank_you_page_if_last_payment_is_not_new(
        OrderProviderInterface $orderProvider,
        UrlGeneratorInterface $router,
        Request $request,
        OrderInterface $order,
        PaymentInterface $lastPayment,
        PaymentInterface $penultimatePayment,
        Session $session,
        FlashBagInterface $flashBag,
    ): void {
        $orderProvider->provide('TOKEN')->willReturn($order);
        $order->getPayments()->willReturn(new ArrayCollection([
            $penultimatePayment->getWrappedObject(),
            $lastPayment->getWrappedObject(),
        ]));

        $lastPayment->getState()->willReturn(PaymentInterface::STATE_COMPLETED);

        $request->getSession()->willReturn($session);
        $session->getFlashBag()->willReturn($flashBag);
        $flashBag->add('info', 'sylius.payment.completed')->shouldBeCalled();

        $router->generate('sylius_shop_order_thank_you')->willReturn('/thank-you');

        $this($request, 'TOKEN')->shouldBeLike(new RedirectResponse('/thank-you'));
    }
}
