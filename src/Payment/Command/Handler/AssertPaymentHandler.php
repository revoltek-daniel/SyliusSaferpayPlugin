<?php

declare(strict_types=1);

namespace CommerceWeavers\SyliusSaferpayPlugin\Payment\Command\Handler;

use CommerceWeavers\SyliusSaferpayPlugin\Exception\PaymentAlreadyCompletedException;
use CommerceWeavers\SyliusSaferpayPlugin\Payment\Command\AssertPaymentCommand;
use CommerceWeavers\SyliusSaferpayPlugin\Payum\Factory\AssertFactoryInterface;
use CommerceWeavers\SyliusSaferpayPlugin\Payum\Factory\ResolveNextCommandFactoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Payum\Core\Payum;
use Payum\Core\Security\TokenInterface;
use Payum\Core\Storage\StorageInterface;
use Psr\Log\LoggerInterface;
use Sylius\Bundle\PayumBundle\Factory\GetStatusFactoryInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Repository\PaymentRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Lock\Exception\LockAcquiringException;
use Symfony\Component\Lock\Exception\LockConflictedException;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;
use Webmozart\Assert\Assert;

final class AssertPaymentHandler
{
    public function __construct(
        private MessageBusInterface $commandBus,
        private Payum $payum,
        private StorageInterface $tokenStorage,
        private AssertFactoryInterface $assertFactory,
        private GetStatusFactoryInterface $getStatusRequestFactory,
        private ResolveNextCommandFactoryInterface $resolveNextCommandFactory,
        private LoggerInterface $logger,
        private PaymentRepositoryInterface $paymentRepository,
        private EntityManagerInterface $entityManager,
        private LockFactory $lockFactory,
    ) {
    }

    public function __invoke(AssertPaymentCommand $command): void
    {
        $lock = $this->lockFactory->createLock('payment_processing');
        try {
            if (!$lock->acquire()) {
                throw new LockAcquiringException('Cannot lock payment processing');
            }
        } catch (LockConflictedException|LockAcquiringException $exception) {
            $this->logger->error('Cannot lock payment processing');

            throw $exception;
        }

        $this->logger->debug('Payment processing locked successfully');

        $this->startProcessingPayment($command->getPaymentId());

        /** @var TokenInterface|null $token */
        $token = $this->tokenStorage->find($command->getPayumToken());
        if ($token === null) {
            $this->logger->debug('AssertPaymentHandler:38 - Token {token} not found', ['token' => $command->getPayumToken()]);
        }
        Assert::notNull($token, 'Token not found.');

        $gateway = $this->payum->getGateway($token->getGatewayName());

        $assert = $this->assertFactory->createNewWithModel($token);
        $gateway->execute($assert);

        /** @var PaymentInterface $payment */
        $payment = $assert->getFirstModel();

        $status = $this->getStatusRequestFactory->createNewWithModel($payment);
        $gateway->execute($status);

        $this->tokenStorage->delete($token);

        $resolvedNextCommand = $this->resolveNextCommandFactory->createNewWithModel($payment);
        $gateway->execute($resolvedNextCommand);

        $lock->release();
        $this->logger->debug('Payment processing lock released');

        $nextCommand = $resolvedNextCommand->getNextCommand();
        if (null === $nextCommand) {
            return;
        }

        $this->commandBus->dispatch($nextCommand, [new DispatchAfterCurrentBusStamp()]);
    }

    private function startProcessingPayment(int $paymentId): void
    {
        /** @var PaymentInterface $payment */
        $payment = $this->paymentRepository->find($paymentId);
        if ($payment->getDetails()['processing_started'] ?? false) {
            throw PaymentAlreadyCompletedException::occur($payment->getId(), $payment->getOrder()->getTokenValue());
        }

        $payment->setDetails(array_merge($payment->getDetails(), ['processing_started' => true]));
        $this->entityManager->flush();
    }
}
