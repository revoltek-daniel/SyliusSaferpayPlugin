<?php

declare(strict_types=1);

namespace CommerceWeavers\SyliusSaferpayPlugin\Controller\Action;

use CommerceWeavers\SyliusSaferpayPlugin\Exception\PaymentAlreadyCompletedException;
use CommerceWeavers\SyliusSaferpayPlugin\Payment\Command\AssertPaymentCommand;
use Doctrine\ORM\EntityManagerInterface;
use Payum\Core\Payum;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Repository\PaymentRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Lock\Exception\LockAcquiringException;
use Symfony\Component\Lock\Exception\LockConflictedException;
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

        $this->logger->debug('Webhhook handled');

        try {
            $this->commandBus->dispatch(
                new AssertPaymentCommand($token->getHash(), $request->attributes->getInt('payment_id'))
            );
        } catch (HandlerFailedException $exception) {
            if (
                $exception->getPrevious() instanceof LockAcquiringException ||
                $exception->getPrevious() instanceof LockConflictedException
            ) {
                $this->logger->error('Webhook - payment already processing synchronously');

                return new JsonResponse(status: Response::HTTP_OK);
            }

            if (!$exception->getPrevious() instanceof PaymentAlreadyCompletedException) {
                $this->logger->error('Webhhook handled failed');

                return new JsonResponse(status: Response::HTTP_BAD_REQUEST);
            }

            $this->logger->debug('Webhook handling - payment already completed');
        }

        $this->logger->debug('Webhook handling - payment webhook finished');

        return new JsonResponse(status: Response::HTTP_OK);
    }
}
