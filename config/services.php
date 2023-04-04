<?php

declare(strict_types=1);

use CommerceWeavers\SyliusSaferpayPlugin\Form\Type\SaferpayGatewayConfigurationType;
use CommerceWeavers\SyliusSaferpayPlugin\Payum\Action\CaptureAction;
use CommerceWeavers\SyliusSaferpayPlugin\Payum\Factory\SaferpayGatewayFactory;
use Payum\Core\Bridge\Symfony\Builder\GatewayFactoryBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator->services();

    $services->set(SaferpayGatewayFactory::class, GatewayFactoryBuilder::class)
        ->args([
            SaferpayGatewayFactory::class,
        ])
        ->tag('payum.gateway_factory_builder', ['factory' => 'saferpay_payment'])
    ;

    $services->set(SaferpayGatewayConfigurationType::class)
        ->tag('sylius.gateway_configuration_type', ['type' => 'saferpay_payment', 'label' => 'Saferpay Payment'])
        ->tag('form.type')
    ;

    $services->set(CaptureAction::class)
        ->public()
        ->args([
            service('sylius.http_client')
        ])
        ->tag('payum.action', ['factory' => 'saferpay_payment', 'alias' => 'payum.action.capture'])
    ;
};
