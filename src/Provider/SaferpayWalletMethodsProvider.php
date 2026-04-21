<?php

declare(strict_types=1);

namespace CommerceWeavers\SyliusSaferpayPlugin\Provider;

use CommerceWeavers\SyliusSaferpayPlugin\Client\SaferpayClientInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Webmozart\Assert\Assert;

class SaferpayWalletMethodsProvider implements SaferpayPaymentMethodsProviderInterface
{
    public function __construct(
        private SaferpayClientInterface $client,
    ) {
    }

    public function provide(PaymentMethodInterface $paymentMethod): array
    {
        $gatewayConfig = $paymentMethod->getGatewayConfig();
        Assert::notNull($gatewayConfig);

        $terminal = $this->client->getTerminal($gatewayConfig);
        if (!isset($terminal['PaymentMethods'])) {
            return [];
        }
        /** @var array $walletData */
        $walletData = $terminal['Wallets'] ?? [];

        return array_map(
            fn (array $paymentMethodData): mixed => $paymentMethodData['WalletName'],
            $walletData,
        );
    }
}
