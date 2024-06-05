<?php

declare(strict_types=1);

namespace CommerceWeavers\SyliusSaferpayPlugin\Payum\Action;

use CommerceWeavers\SyliusSaferpayPlugin\Client\SaferpayClientInterface;
use CommerceWeavers\SyliusSaferpayPlugin\Client\ValueObject\CaptureResponse;
use CommerceWeavers\SyliusSaferpayPlugin\Client\ValueObject\ErrorResponse;
use CommerceWeavers\SyliusSaferpayPlugin\Payum\Status\StatusCheckerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\Capture;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Symfony\Component\HttpFoundation\Response;

final class CaptureAction implements ActionInterface
{
    public function __construct(
        private SaferpayClientInterface $saferpayClient,
        private StatusCheckerInterface $statusChecker,
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager
    ) {
    }

    /** @param Capture $request */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var PaymentInterface $payment */
        $payment = $request->getModel();

        if ($this->statusChecker->isCaptured($payment)) {
            return;
        }

        /** @var CaptureResponse|ErrorResponse $response */
        $response = $this->saferpayClient->capture($payment);
        if ($response instanceof ErrorResponse) {
            if ($response->getName() === 'TRANSACTION_ALREADY_CAPTURED') {
                $this->entityManager->refresh($payment);

                if ($payment->getState() === PaymentInterface::STATE_COMPLETED) {
                    $this->logger->debug('Capture failed for payment: ' . $payment->getId() . ' already captured and state complete', ['details' => $payment->getDetails()]);
                    return;
                }
                $this->logger->debug('Capture failed for payment: ' . $payment->getId() . ' already captured', ['state' => $payment->getState(), 'details' => $payment->getDetails()]);
            }

            $this->logger->debug('Capture failed for payment: ' . $payment->getId(), ['response' => $response->toArray()]);
            $payment->setDetails(array_merge($payment->getDetails(), [
                'status' => StatusAction::STATUS_FAILED,
                'transaction_id' => $response->getTransactionId(),
            ]));

            return;
        }

        $paymentDetails = $payment->getDetails();
        $isSuccessfulResponse = $response->getStatusCode() === Response::HTTP_OK;
        $paymentDetails['status'] = $isSuccessfulResponse ? $response->getStatus() : StatusAction::STATUS_FAILED;
        $paymentDetails['capture_id'] = $response->getCaptureId();

        $payment->setDetails($paymentDetails);
    }

    public function supports($request): bool
    {
        return $request instanceof Capture && $request->getFirstModel() instanceof PaymentInterface;
    }
}
