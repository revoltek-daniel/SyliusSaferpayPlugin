<?php

declare(strict_types=1);

namespace CommerceWeavers\SyliusSaferpayPlugin\Controller\Action;

use CommerceWeavers\SyliusSaferpayPlugin\Exception\OrderAlreadyCompletedException;
use CommerceWeavers\SyliusSaferpayPlugin\Exception\PaymentAlreadyCompletedException;
use CommerceWeavers\SyliusSaferpayPlugin\Payum\Provider\TokenProviderInterface;
use CommerceWeavers\SyliusSaferpayPlugin\Provider\PaymentProviderInterface;
use Psr\Log\LoggerInterface;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfigurationFactoryInterface;
use Sylius\Component\Resource\Metadata\MetadataInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class PrepareCaptureAction
{
    public function __construct(
        private RequestConfigurationFactoryInterface $requestConfigurationFactory,
        private MetadataInterface $orderMetadata,
        private PaymentProviderInterface $paymentProvider,
        private TokenProviderInterface $tokenProvider,
        private UrlGeneratorInterface $router,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(Request $request, string $tokenValue): RedirectResponse
    {
        $requestConfiguration = $this->requestConfigurationFactory->create($this->orderMetadata, $request);

        try {
            $lastPayment = $this->paymentProvider->provideForCapture($tokenValue);
        } catch (PaymentAlreadyCompletedException|OrderAlreadyCompletedException) {
            $this->logger->debug('PrepareCaptureAction:37 - payment already completed');

            return new RedirectResponse($this->router->generate('sylius_shop_order_thank_you'));
        }

        $token = $this->tokenProvider->provideForCapture($lastPayment, $requestConfiguration);

        return new RedirectResponse($token->getTargetUrl());
    }
}
