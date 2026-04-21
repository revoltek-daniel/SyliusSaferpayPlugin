<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use winzou\Bundle\StateMachineBundle\winzouStateMachineBundle;

return static function (ContainerConfigurator $containerConfigurator) {
    if (class_exists(winzouStateMachineBundle::class) === false) {
        return;
    }

    $containerConfigurator->extension('winzou_state_machine', [
        'sylius_payment' => [
            'callbacks' => [
                'before' => [
                    'refund_saferpay_payment' => [
                        'on' => ['refund'],
                        'do' => ['@CommerceWeavers\SyliusSaferpayPlugin\Processor\RefundProcessorInterface', 'process'],
                        'args' => ['object'],
                        'priority' => -100,
                    ],
                ],
            ],
        ],
    ]);
};
