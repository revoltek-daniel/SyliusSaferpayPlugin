<?php

declare(strict_types=1);

namespace CommerceWeavers\SyliusSaferpayPlugin\Controller\Action;

use CommerceWeavers\SyliusSaferpayPlugin\Exception\OrderAlreadyCompletedException;
use CommerceWeavers\SyliusSaferpayPlugin\Exception\PaymentAlreadyCompletedException;
use CommerceWeavers\SyliusSaferpayPlugin\Payment\Command\AssertPaymentCommand;
use CommerceWeavers\SyliusSaferpayPlugin\Payum\Provider\TokenProviderInterface;
use CommerceWeavers\SyliusSaferpayPlugin\Provider\PaymentProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfigurationFactoryInterface;
use Sylius\Component\Resource\Metadata\MetadataInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Lock\Exception\LockAcquiringException;
use Symfony\Component\Lock\Exception\LockConflictedException;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class PrepareAssertAction
{
    public function __construct(
        private RequestConfigurationFactoryInterface $requestConfigurationFactory,
        private MetadataInterface $orderMetadata,
        private PaymentProviderInterface $paymentProvider,
        private TokenProviderInterface $tokenProvider,
        private UrlGeneratorInterface $router,
        private LoggerInterface $logger,
        private MessageBusInterface $commandBus,
    ) {
    }

    public function __invoke(Request $request, string $tokenValue): RedirectResponse
    {
        $this->logger->debug('Synchronized processing started');

        $requestConfiguration = $this->requestConfigurationFactory->create($this->orderMetadata, $request);

        try {
            $lastPayment = $this->paymentProvider->provideForAssert($tokenValue);

            $token = $this->tokenProvider->provideForAssert($lastPayment, $requestConfiguration);

            $this->commandBus->dispatch(new AssertPaymentCommand($token->getHash(), $lastPayment->getId()));
        } catch (PaymentAlreadyCompletedException|OrderAlreadyCompletedException) {
            $this->logger->debug('Synchronized processing - payment already completed');

            return new RedirectResponse($this->router->generate('sylius_shop_order_thank_you'));
        } catch (HandlerFailedException $exception) {
            if ($exception->getPrevious() instanceof PaymentAlreadyCompletedException) {
                $this->logger->debug('Synchronized processing - payment already completed');

                return new RedirectResponse($this->router->generate('sylius_shop_order_thank_you'));
            }

            if (
                $exception->getPrevious() instanceof LockAcquiringException ||
                $exception->getPrevious() instanceof LockConflictedException
            ) {
                $this->logger->error('Synchronized processing - payment already processing via webhook');

                return new RedirectResponse($this->router->generate('sylius_shop_order_thank_you'));
            }

            $this->logger->debug('Synchronized processing failed');

            return new RedirectResponse($this->router->generate('sylius_shop_order_show', ['tokenValue' => $tokenValue]));
        }

        $this->logger->debug('Synchronized processing finished');

        return new RedirectResponse($this->router->generate('sylius_shop_order_thank_you'));
    }
}
