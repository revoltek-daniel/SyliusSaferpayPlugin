<?php

declare(strict_types=1);

namespace CommerceWeavers\SyliusSaferpayPlugin\Controller\Action;

use CommerceWeavers\SyliusSaferpayPlugin\Exception\PaymentAlreadyCompletedException;
use CommerceWeavers\SyliusSaferpayPlugin\Payment\Command\AssertPaymentCommand;
use Payum\Core\Payum;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;

final class WebhookAction
{
    public function __construct(
        private Payum $payum,
        private MessageBusInterface $commandBus,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $token = $this->payum->getHttpRequestVerifier()->verify($request);

        $this->logger->debug('WebhookAction:34 - Webhook for token {token} received', ['token' => $token->getHash()]);

        try {
            $this->commandBus->dispatch(new AssertPaymentCommand($token->getHash()));
        } catch (HandlerFailedException $exception) {
            if (!$exception->getPrevious() instanceof PaymentAlreadyCompletedException) {
                $this->logger->error('WebhookAction:40 - {exception}', ['exception' => $exception]);

                return new JsonResponse(status: Response::HTTP_BAD_REQUEST);
            }

            $this->logger->debug('WebhookAction:45 - payment already completed');
        }

        $this->logger->debug('WebhookAction:48 - payment webhook finished');

        return new JsonResponse(status: Response::HTTP_OK);
    }
}
